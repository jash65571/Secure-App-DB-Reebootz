// File: server/controllers/user.js
// Description: User management controller
const asyncHandler = require('express-async-handler');
const User = require('../models/User');
const generateRandomPassword = require('../utils/generatePassword');

// @desc    Get all users
// @route   GET /api/users
// @access  Private/Admin
const getUsers = asyncHandler(async (req, res) => {
  const users = await User.find({}).select('-password');
  res.json(users);
});

// @desc    Get user by ID
// @route   GET /api/users/:id
// @access  Private/Admin
const getUserById = asyncHandler(async (req, res) => {
  const user = await User.findById(req.params.id).select('-password');

  if (user) {
    res.json(user);
  } else {
    res.status(404);
    throw new Error('User not found');
  }
});

// @desc    Create a new user
// @route   POST /api/users
// @access  Private/Admin
const createUser = asyncHandler(async (req, res) => {
  const { name, email, role, storeId, warehouseId } = req.body;

  const userExists = await User.findOne({ email });

  if (userExists) {
    res.status(400);
    throw new Error('User already exists');
  }

  // Generate a random password for the new user
  const password = generateRandomPassword();

  const user = await User.create({
    name,
    email,
    password,
    role,
    storeId,
    warehouseId,
  });

  if (user) {
    res.status(201).json({
      _id: user._id,
      name: user.name,
      email: user.email,
      role: user.role,
      tempPassword: password, // In a real application, this would be sent by email
    });
  } else {
    res.status(400);
    throw new Error('Invalid user data');
  }
});

// @desc    Update user
// @route   PUT /api/users/:id
// @access  Private/Admin
const updateUser = asyncHandler(async (req, res) => {
  const user = await User.findById(req.params.id);

  if (user) {
    user.name = req.body.name || user.name;
    user.email = req.body.email || user.email;
    user.role = req.body.role || user.role;
    user.isActive = req.body.isActive !== undefined ? req.body.isActive : user.isActive;
    
    if (req.body.storeId) {
      user.storeId = req.body.storeId;
    }
    
    if (req.body.warehouseId) {
      user.warehouseId = req.body.warehouseId;
    }

    const updatedUser = await user.save();

    res.json({
      _id: updatedUser._id,
      name: updatedUser.name,
      email: updatedUser.email,
      role: updatedUser.role,
      isActive: updatedUser.isActive,
    });
  } else {
    res.status(404);
    throw new Error('User not found');
  }
});

// @desc    Delete user
// @route   DELETE /api/users/:id
// @access  Private/Admin
const deleteUser = asyncHandler(async (req, res) => {
  const user = await User.findById(req.params.id);

  if (user) {
    // Instead of deleting, we deactivate the user
    user.isActive = false;
    await user.save();
    res.json({ message: 'User deactivated' });
  } else {
    res.status(404);
    throw new Error('User not found');
  }
});

module.exports = {
  getUsers,
  getUserById,
  createUser,
  updateUser,
  deleteUser,
};

// File: server/controllers/warehouse.js
// Description: Warehouse management controller
const asyncHandler = require('express-async-handler');
const Warehouse = require('../models/Warehouse');
const User = require('../models/User');
const generateRandomPassword = require('../utils/generatePassword');

// @desc    Get all warehouses
// @route   GET /api/warehouses
// @access  Private/Admin
const getWarehouses = asyncHandler(async (req, res) => {
  const warehouses = await Warehouse.find({});
  res.json(warehouses);
});

// @desc    Get warehouse by ID
// @route   GET /api/warehouses/:id
// @access  Private/Admin
const getWarehouseById = asyncHandler(async (req, res) => {
  const warehouse = await Warehouse.findById(req.params.id);

  if (warehouse) {
    res.json(warehouse);
  } else {
    res.status(404);
    throw new Error('Warehouse not found');
  }
});

// @desc    Create a new warehouse with admin user
// @route   POST /api/warehouses
// @access  Private/Admin
const createWarehouse = asyncHandler(async (req, res) => {
  const { name, address, contactPerson, email, phone, adminEmail, adminName } = req.body;

  // Create the warehouse
  const warehouse = await Warehouse.create({
    name,
    address,
    contactPerson,
    email,
    phone,
  });

  if (warehouse) {
    // Generate a random password for the warehouse admin
    const password = generateRandomPassword();

    // Create a warehouse admin user
    const warehouseAdmin = await User.create({
      name: adminName || contactPerson,
      email: adminEmail || email,
      password,
      role: 'warehouse',
      warehouseId: warehouse._id,
    });

    if (warehouseAdmin) {
      res.status(201).json({
        warehouse: {
          _id: warehouse._id,
          name: warehouse.name,
        },
        admin: {
          _id: warehouseAdmin._id,
          name: warehouseAdmin.name,
          email: warehouseAdmin.email,
          tempPassword: password, // In a real application, this would be sent by email
        },
      });
    } else {
      // If admin creation fails, delete the warehouse
      await Warehouse.findByIdAndDelete(warehouse._id);
      res.status(400);
      throw new Error('Failed to create warehouse admin');
    }
  } else {
    res.status(400);
    throw new Error('Invalid warehouse data');
  }
});

// @desc    Update warehouse
// @route   PUT /api/warehouses/:id
// @access  Private/Admin
const updateWarehouse = asyncHandler(async (req, res) => {
  const warehouse = await Warehouse.findById(req.params.id);

  if (warehouse) {
    warehouse.name = req.body.name || warehouse.name;
    warehouse.address = req.body.address || warehouse.address;
    warehouse.contactPerson = req.body.contactPerson || warehouse.contactPerson;
    warehouse.email = req.body.email || warehouse.email;
    warehouse.phone = req.body.phone || warehouse.phone;
    warehouse.isActive = req.body.isActive !== undefined ? req.body.isActive : warehouse.isActive;

    const updatedWarehouse = await warehouse.save();
    res.json(updatedWarehouse);
  } else {
    res.status(404);
    throw new Error('Warehouse not found');
  }
});

// @desc    Delete/deactivate warehouse
// @route   DELETE /api/warehouses/:id
// @access  Private/Admin
const deleteWarehouse = asyncHandler(async (req, res) => {
  const warehouse = await Warehouse.findById(req.params.id);

  if (warehouse) {
    // Instead of deleting, we deactivate the warehouse
    warehouse.isActive = false;
    await warehouse.save();
    
    // Also deactivate related warehouse admin users
    await User.updateMany(
      { warehouseId: warehouse._id },
      { isActive: false }
    );
    
    res.json({ message: 'Warehouse deactivated' });
  } else {
    res.status(404);
    throw new Error('Warehouse not found');
  }
});

module.exports = {
  getWarehouses,
  getWarehouseById,
  createWarehouse,
  updateWarehouse,
  deleteWarehouse,
};

// File: server/controllers/store.js
// Description: Store management controller
const asyncHandler = require('express-async-handler');
const Store = require('../models/Store');
const User = require('../models/User');
const Device = require('../models/Device');
const generateRandomPassword = require('../utils/generatePassword');

// @desc    Get all stores
// @route   GET /api/stores
// @access  Private/Admin/Warehouse
const getStores = asyncHandler(async (req, res) => {
  // If user is a warehouse admin, only show stores from their warehouse
  let filter = {};
  
  if (req.user.role === 'warehouse') {
    filter = { warehouseId: req.user.warehouseId };
  }
  
  const stores = await Store.find(filter);
  res.json(stores);
});

// @desc    Get store by ID with stock statistics
// @route   GET /api/stores/:id
// @access  Private/Admin/Warehouse
const getStoreById = asyncHandler(async (req, res) => {
  const store = await Store.findById(req.params.id);

  if (!store) {
    res.status(404);
    throw new Error('Store not found');
  }

  // Check authorization - warehouse users can only view their own stores
  if (
    req.user.role === 'warehouse' && 
    store.warehouseId.toString() !== req.user.warehouseId.toString()
  ) {
    res.status(403);
    throw new Error('Not authorized to access this store');
  }

  // Get device statistics for this store
  const totalDevicesSent = await Device.countDocuments({
    storeId: store._id,
  });
  
  const totalDevicesSold = await Device.countDocuments({
    storeId: store._id,
    status: 'sold',
  });
  
  const totalDevicesReturned = await Device.countDocuments({
    storeId: store._id,
    status: 'returned',
  });
  
  const totalDevicesOnStock = await Device.countDocuments({
    storeId: store._id,
    status: 'transferred',
  });

  // Get store admins
  const storeAdmins = await User.find({ 
    storeId: store._id, 
    role: 'store'
  }).select('-password');

  res.json({
    ...store.toObject(),
    statistics: {
      totalDevicesSent,
      totalDevicesSold,
      totalDevicesReturned,
      totalDevicesOnStock,
    },
    storeAdmins,
  });
});

// @desc    Create a new store with admin user
// @route   POST /api/stores
// @access  Private/Admin/Warehouse
const createStore = asyncHandler(async (req, res) => {
  const { 
    name, 
    address, 
    pan, 
    gst, 
    contactPerson, 
    email, 
    phone 
  } = req.body;

  // If warehouse admin, use their warehouse ID
  let warehouseId = req.body.warehouseId;
  if (req.user.role === 'warehouse') {
    warehouseId = req.user.warehouseId;
  }

  // Check if store name already exists
  const storeExists = await Store.findOne({ name });
  if (storeExists) {
    res.status(400);
    throw new Error('Store with this name already exists');
  }

  // Create the store
  const store = await Store.create({
    name,
    address,
    pan,
    gst,
    contactPerson,
    email,
    phone,
    warehouseId,
  });

  if (store) {
    // Generate store admin username and password
    const storeUsername = `${name.toLowerCase().replace(/[^a-z0-9]/g, '')}_admin`;
    const password = generateRandomPassword();

    // Create a store admin user
    const storeAdmin = await User.create({
      name: contactPerson,
      email: email,
      password,
      role: 'store',
      storeId: store._id,
    });

    if (storeAdmin) {
      res.status(201).json({
        store: {
          _id: store._id,
          name: store.name,
        },
        admin: {
          _id: storeAdmin._id,
          name: storeAdmin.name,
          email: storeAdmin.email,
          username: storeUsername,
          tempPassword: password, // In a real application, this would be sent by email
        },
      });
    } else {
      // If admin creation fails, delete the store
      await Store.findByIdAndDelete(store._id);
      res.status(400);
      throw new Error('Failed to create store admin');
    }
  } else {
    res.status(400);
    throw new Error('Invalid store data');
  }
});

// @desc    Update store
// @route   PUT /api/stores/:id
// @access  Private/Admin/Warehouse
const updateStore = asyncHandler(async (req, res) => {
  const store = await Store.findById(req.params.id);

  if (!store) {
    res.status(404);
    throw new Error('Store not found');
  }

  // Check authorization - warehouse users can only update their own stores
  if (
    req.user.role === 'warehouse' && 
    store.warehouseId.toString() !== req.user.warehouseId.toString()
  ) {
    res.status(403);
    throw new Error('Not authorized to update this store');
  }

  store.name = req.body.name || store.name;
  store.address = req.body.address || store.address;
  store.pan = req.body.pan || store.pan;
  store.gst = req.body.gst || store.gst;
  store.contactPerson = req.body.contactPerson || store.contactPerson;
  store.email = req.body.email || store.email;
  store.phone = req.body.phone || store.phone;
  store.isActive = req.body.isActive !== undefined ? req.body.isActive : store.isActive;

  const updatedStore = await store.save();
  
  res.json(updatedStore);
});

// @desc    Delete/deactivate store
// @route   DELETE /api/stores/:id
// @access  Private/Admin/Warehouse
const deleteStore = asyncHandler(async (req, res) => {
  const store = await Store.findById(req.params.id);

  if (!store) {
    res.status(404);
    throw new Error('Store not found');
  }

  // Check authorization - warehouse users can only delete their own stores
  if (
    req.user.role === 'warehouse' && 
    store.warehouseId.toString() !== req.user.warehouseId.toString()
  ) {
    res.status(403);
    throw new Error('Not authorized to delete this store');
  }

  // Check if there are devices in stock at this store
  const devicesInStock = await Device.countDocuments({
    storeId: store._id,
    status: 'transferred',
  });

  if (devicesInStock > 0) {
    res.status(400);
    throw new Error(`Cannot deactivate store with ${devicesInStock} device(s) in stock. Please transfer or return all devices first.`);
  }

  // Instead of deleting, we deactivate the store
  store.isActive = false;
  await store.save();
  
  // Also deactivate related store admin users
  await User.updateMany(
    { storeId: store._id },
    { isActive: false }
  );
  
  res.json({ message: 'Store deactivated' });
});

module.exports = {
  getStores,
  getStoreById,
  createStore,
  updateStore,
  deleteStore,
};

// File: server/controllers/device.js
// Description: Device management controller
const asyncHandler = require('express-async-handler');
const Device = require('../models/Device');
const Store = require('../models/Store');

// @desc    Get all devices with filtering
// @route   GET /api/devices
// @access  Private/Admin/Warehouse/Store
const getDevices = asyncHandler(async (req, res) => {
  const { status, model, storeId, warehouseId } = req.query;
  
  let filter = {};
  
  // Apply role-based filters
  if (req.user.role === 'warehouse') {
    filter.warehouseId = req.user.warehouseId;
  } else if (req.user.role === 'store') {
    filter.storeId = req.user.storeId;
  }
  
  // Apply additional filters from query parameters
  if (status) filter.status = status;
  if (model) filter.model = { $regex: model, $options: 'i' };
  if (storeId && (req.user.role === 'admin' || req.user.role === 'warehouse')) {
    filter.storeId = storeId;
  }
  if (warehouseId && req.user.role === 'admin') {
    filter.warehouseId = warehouseId;
  }

  const devices = await Device.find(filter)
    .populate('warehouseId', 'name')
    .populate('storeId', 'name');
    
  res.json(devices);
});

// @desc    Get device by ID
// @route   GET /api/devices/:id
// @access  Private/Admin/Warehouse/Store
const getDeviceById = asyncHandler(async (req, res) => {
  const device = await Device.findById(req.params.id)
    .populate('warehouseId', 'name')
    .populate('storeId', 'name')
    .populate('logs.performedBy', 'name role');

  if (!device) {
    res.status(404);
    throw new Error('Device not found');
  }

  // Authorization check
  if (
    (req.user.role === 'warehouse' && device.warehouseId._id.toString() !== req.user.warehouseId.toString()) ||
    (req.user.role === 'store' && (!device.storeId || device.storeId._id.toString() !== req.user.storeId.toString()))
  ) {
    res.status(403);
    throw new Error('Not authorized to access this device');
  }

  res.json(device);
});

// @desc    Create a new device
// @route   POST /api/devices
// @access  Private/Warehouse
const createDevice = asyncHandler(async (req, res) => {
  const { name, model, imei1, imei2, testResults } = req.body;

  // Check if device with IMEI already exists
  const deviceExists = await Device.findOne({ imei1 });
  if (deviceExists) {
    res.status(400);
    throw new Error('Device with this IMEI already exists');
  }

  // Create device
  const device = await Device.create({
    name,
    model,
    imei1,
    imei2,
    warehouseId: req.user.warehouseId,
    testResults,
    logs: [
      {
        action: 'created',
        performedBy: req.user._id,
        details: 'Device created in warehouse inventory',
      },
    ],
  });

  if (device) {
    res.status(201).json(device);
  } else {
    res.status(400);
    throw new Error('Invalid device data');
  }
});

// @desc    Update device
// @route   PUT /api/devices/:id
// @access  Private/Warehouse
const updateDevice = asyncHandler(async (req, res) => {
  const device = await Device.findById(req.params.id);

  if (!device) {
    res.status(404);
    throw new Error('Device not found');
  }

  // Authorization check
  if (req.user.role === 'warehouse' && device.warehouseId.toString() !== req.user.warehouseId.toString()) {
    res.status(403);
    throw new Error('Not authorized to update this device');
  }

  // Update fields
  device.name = req.body.name || device.name;
  device.model = req.body.model || device.model;
  device.testResults = req.body.testResults || device.testResults;
  
  if (req.body.dateOfPurchase) {
    device.dateOfPurchase = req.body.dateOfPurchase;
  }

  // Add log entry for the update
  device.logs.push({
    action: 'updated',
    performedBy: req.user._id,
    details: 'Device information updated',
  });

  const updatedDevice = await device.save();
  res.json(updatedDevice);
});

// @desc    Transfer device to store
// @route   POST /api/devices/:id/transfer
// @access  Private/Warehouse
const transferDevice = asyncHandler(async (req, res) => {
  const { storeId } = req.body;
  
  if (!storeId) {
    res.status(400);
    throw new Error('Store ID is required');
  }

  const device = await Device.findById(req.params.id);
  if (!device) {
    res.status(404);
    throw new Error('Device not found');
  }

  // Authorization check
  if (req.user.role === 'warehouse' && device.warehouseId.toString() !== req.user.warehouseId.toString()) {
    res.status(403);
    throw new Error('Not authorized to transfer this device');
  }

  // Check device status
  if (device.status !== 'in-warehouse' && device.status !== 'returned') {
    res.status(400);
    throw new Error(`Cannot transfer device with status: ${device.status}`);
  }

  // Check if store exists and is active
  const store = await Store.findById(storeId);
  if (!store) {
    res.status(404);
    throw new Error('Store not found');
  }
  
  if (!store.isActive) {
    res.status(400);
    throw new Error('Cannot transfer to inactive store');
  }
  
  // Check if store belongs to the warehouse
  if (req.user.role === 'warehouse' && store.warehouseId.toString() !== req.user.warehouseId.toString()) {
    res.status(403);
    throw new Error('Not authorized to transfer to this store');
  }

  // Update device
  device.status = 'transferred';
  device.storeId = storeId;
  device.storeName = store.name;

  // Add log entry
  device.logs.push({
    action: 'transferred',
    performedBy: req.user._id,
    details: `Device transferred to store: ${store.name}`,
  });

  const updatedDevice = await device.save();
  res.json(updatedDevice);
});

// @desc    Mark device as sold
// @route   POST /api/devices/:id/sell
// @access  Private/Store
const sellDevice = asyncHandler(async (req, res) => {
  const { customerInfo, dateOfSale } = req.body;
  
  const device = await Device.findById(req.params.id);
  if (!device) {
    res.status(404);
    throw new Error('Device not found');
  }

  // Authorization check
  if (req.user.role === 'store' && (!device.storeId || device.storeId.toString() !== req.user.storeId.toString())) {
    res.status(403);
    throw new Error('Not authorized to sell this device');
  }

  // Check device status
  if (device.status !== 'transferred') {
    res.status(400);
    throw new Error(`Cannot sell device with status: ${device.status}`);
  }

  // Update device
  device.status = 'sold';
  device.dateOfSale = dateOfSale || new Date();

  // Add log entry
  device.logs.push({
    action: 'sold',
    performedBy: req.user._id,
    details: `Device sold to customer${customerInfo ? `: ${JSON.stringify(customerInfo)}` : ''}`,
  });

  const updatedDevice = await device.save();
  res.json(updatedDevice);
});

// @desc    Return device to warehouse
// @route   POST /api/devices/:id/return
// @access  Private/Store
const returnDevice = asyncHandler(async (req, res) => {
  const { reason } = req.body;
  
  const device = await Device.findById(req.params.id);
  if (!device) {
    res.status(404);
    throw new Error('Device not found');
  }

  // Authorization check
  if (req.user.role === 'store' && (!device.storeId || device.storeId.toString() !== req.user.storeId.toString())) {
    res.status(403);
    throw new Error('Not authorized to return this device');
  }

  // Check device status
  if (device.status !== 'transferred') {
    res.status(400);
    throw new Error(`Cannot return device with status: ${device.status}`);
  }

  // Update device
  device.status = 'returned';

  // Add log entry
  device.logs.push({
    action: 'returned',
    performedBy: req.user._id,
    details: `Device returned to warehouse${reason ? ` - Reason: ${reason}` : ''}`,
  });

  const updatedDevice = await device.save();
  res.json(updatedDevice);
});

// @desc    Get device statistics and counts
// @route   GET /api/devices/statistics
// @access  Private/Admin/Warehouse
const getDeviceStatistics = asyncHandler(async (req, res) => {
  let filter = {};
  
  // Role-based filtering
  if (req.user.role === 'warehouse') {
    filter.warehouseId = req.user.warehouseId;
  }

  // Total counts
  const totalDevices = await Device.countDocuments(filter);
  
  const devicesTransferred = await Device.countDocuments({
    ...filter,
    status: 'transferred',
  });
  
  const devicesSold = await Device.countDocuments({
    ...filter,
    status: 'sold',
  });
  
  const devicesReturned = await Device.countDocuments({
    ...filter,
    status: 'returned',
  });
  
  const devicesInWarehouse = await Device.countDocuments({
    ...filter,
    status: 'in-warehouse',
  });

  // Get counts by model
  const modelCounts = await Device.aggregate([
    { $match: filter },
    { $group: { _id: '$model', count: { $sum: 1 } } },
    { $sort: { count: -1 } },
  ]);

  // Get counts by store
  const storeCounts = await Device.aggregate([
    { $match: { ...filter, storeId: { $exists: true } } },
    { $group: { _id: '$storeId', count: { $sum: 1 } } },
  ]);

  // Populate store names
  const storeIds = storeCounts.map(store => store._id);
  const stores = await Store.find({ _id: { $in: storeIds } });
  
  const storeCountsWithNames = storeCounts.map(store => {
    const storeData = stores.find(s => s._id.toString() === store._id.toString());
    return {
      _id: store._id,
      name: storeData ? storeData.name : 'Unknown Store',
      count: store.count,
    };
  });

  res.json({
    counts: {
      totalDevices,
      devicesTransferred,
      devicesSold,
      devicesReturned,
      devicesInWarehouse,
    },
    modelCounts,
    storeCounts: storeCountsWithNames,
  });
});

module.exports = {
  getDevices,
  getDeviceById,
  createDevice,
  updateDevice,
  transferDevice,
  sellDevice,
  returnDevice,
  getDeviceStatistics,
};
