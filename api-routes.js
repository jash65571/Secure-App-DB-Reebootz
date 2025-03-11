// File: server/routes/auth.js
// Description: Authentication routes
const express = require('express');
const { 
  loginUser, 
  getUserProfile, 
  updateUserProfile, 
  resetUserPassword 
} = require('../controllers/auth');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

router.post('/login', loginUser);
router.get('/profile', protect, getUserProfile);
router.put('/profile', protect, updateUserProfile);
router.post('/reset-password', protect, authorize('admin', 'superadmin'), resetUserPassword);

module.exports = router;

// File: server/routes/user.js
// Description: User management routes
const express = require('express');
const {
  getUsers,
  getUserById,
  createUser,
  updateUser,
  deleteUser,
} = require('../controllers/user');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

router
  .route('/')
  .get(protect, authorize('admin', 'superadmin'), getUsers)
  .post(protect, authorize('admin', 'superadmin'), createUser);

router
  .route('/:id')
  .get(protect, authorize('admin', 'superadmin'), getUserById)
  .put(protect, authorize('admin', 'superadmin'), updateUser)
  .delete(protect, authorize('admin', 'superadmin'), deleteUser);

module.exports = router;

// File: server/routes/warehouse.js
// Description: Warehouse management routes
const express = require('express');
const {
  getWarehouses,
  getWarehouseById,
  createWarehouse,
  updateWarehouse,
  deleteWarehouse,
} = require('../controllers/warehouse');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

router
  .route('/')
  .get(protect, authorize('admin', 'superadmin'), getWarehouses)
  .post(protect, authorize('admin', 'superadmin'), createWarehouse);

router
  .route('/:id')
  .get(protect, authorize('admin', 'superadmin'), getWarehouseById)
  .put(protect, authorize('admin', 'superadmin'), updateWarehouse)
  .delete(protect, authorize('admin', 'superadmin'), deleteWarehouse);

module.exports = router;

// File: server/routes/store.js
// Description: Store management routes
const express = require('express');
const {
  getStores,
  getStoreById,
  createStore,
  updateStore,
  deleteStore,
} = require('../controllers/store');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

router
  .route('/')
  .get(protect, authorize('admin', 'superadmin', 'warehouse'), getStores)
  .post(protect, authorize('admin', 'superadmin', 'warehouse'), createStore);

router
  .route('/:id')
  .get(protect, authorize('admin', 'superadmin', 'warehouse'), getStoreById)
  .put(protect, authorize('admin', 'superadmin', 'warehouse'), updateStore)
  .delete(protect, authorize('admin', 'superadmin', 'warehouse'), deleteStore);

module.exports = router;

// File: server/routes/device.js
// Description: Device management routes
const express = require('express');
const {
  getDevices,
  getDeviceById,
  createDevice,
  updateDevice,
  transferDevice,
  sellDevice,
  returnDevice,
  getDeviceStatistics,
} = require('../controllers/device');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

router
  .route('/')
  .get(protect, authorize('admin', 'superadmin', 'warehouse', 'store'), getDevices)
  .post(protect, authorize('warehouse'), createDevice);

router.get(
  '/statistics',
  protect,
  authorize('admin', 'superadmin', 'warehouse'),
  getDeviceStatistics
);

router
  .route('/:id')
  .get(protect, authorize('admin', 'superadmin', 'warehouse', 'store'), getDeviceById)
  .put(protect, authorize('warehouse'), updateDevice);

router.post(
  '/:id/transfer',
  protect,
  authorize('warehouse'),
  transferDevice
);

router.post(
  '/:id/sell',
  protect,
  authorize('store'),
  sellDevice
);

router.post(
  '/:id/return',
  protect,
  authorize('store'),
  returnDevice
);

module.exports = router;

// File: server/server.js
// Description: Main server file
const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const path = require('path');
const connectDB = require('./config/db');
const { errorHandler } = require('./middleware/errorHandler');
require('dotenv').config();

// Connect to database
connectDB();

// Create Express app
const app = express();

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(cors());
app.use(morgan('dev'));

// API Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/users', require('./routes/user'));
app.use('/api/warehouses', require('./routes/warehouse'));
app.use('/api/stores', require('./routes/store'));
app.use('/api/devices', require('./routes/device'));

// Error Handling middleware
app.use(errorHandler);

// Create initial superadmin user if not exists
const createSuperAdmin = require('./utils/createSuperAdmin');
createSuperAdmin();

// Serve frontend in production
if (process.env.NODE_ENV === 'production') {
  app.use(express.static(path.join(__dirname, '../client/build')));

  app.get('*', (req, res) =>
    res.sendFile(
      path.resolve(__dirname, '../', 'client', 'build', 'index.html')
    )
  );
}

// Start server
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

// File: server/middleware/errorHandler.js
// Description: Error handling middleware
const errorHandler = (err, req, res, next) => {
  const statusCode = res.statusCode === 200 ? 500 : res.statusCode;
  
  console.error(`Error: ${err.message}`);
  console.error(err.stack);
  
  res.status(statusCode);
  res.json({
    message: err.message,
    stack: process.env.NODE_ENV === 'production' ? null : err.stack,
  });
};

module.exports = { errorHandler };

// File: server/utils/createSuperAdmin.js
// Description: Utility to create initial superadmin user
const User = require('../models/User');
const bcrypt = require('bcryptjs');

const createSuperAdmin = async () => {
  try {
    // Check if superadmin already exists
    const superadminExists = await User.findOne({ role: 'superadmin' });
    
    if (!superadminExists) {
      // Get email and password from environment variables
      const email = process.env.SUPERADMIN_EMAIL || 'admin@secureapp.com';
      let password = process.env.SUPERADMIN_PASSWORD || 'Admin@123456';
      
      // Hash password
      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(password, salt);
      
      // Create superadmin
      await User.create({
        name: 'Super Admin',
        email,
        password: hashedPassword,
        role: 'superadmin',
      });
      
      console.log('Superadmin user created successfully');
      console.log(`Email: ${email}`);
      console.log(`Password: ${password}`);
    }
  } catch (error) {
    console.error('Error creating superadmin:', error.message);
  }
};

module.exports = createSuperAdmin;

// File: .env
// Description: Environment variables (create this file in the root directory)
NODE_ENV=development
PORT=5000
MONGO_URI=mongodb://localhost:27017/secureapp
JWT_SECRET=your_jwt_secret_key_goes_here
SUPERADMIN_EMAIL=admin@secureapp.com
SUPERADMIN_PASSWORD=Admin@123456
