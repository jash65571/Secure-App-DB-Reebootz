// File: server/config/db.js
// Description: Database connection setup
const mongoose = require('mongoose');
require('dotenv').config();

const connectDB = async () => {
  try {
    const conn = await mongoose.connect(process.env.MONGO_URI, {
      useNewUrlParser: true,
      useUnifiedTopology: true,
    });
    console.log(`MongoDB Connected: ${conn.connection.host}`);
  } catch (error) {
    console.error(`Error: ${error.message}`);
    process.exit(1);
  }
};

module.exports = connectDB;

// File: server/models/User.js
// Description: User model for authentication and authorization
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');

const userSchema = mongoose.Schema(
  {
    name: {
      type: String,
      required: true,
    },
    email: {
      type: String,
      required: true,
      unique: true,
    },
    password: {
      type: String,
      required: true,
    },
    role: {
      type: String,
      enum: ['superadmin', 'admin', 'warehouse', 'store', 'customer'],
      default: 'store',
    },
    storeId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Store',
      required: function() {
        return this.role === 'store';
      },
    },
    warehouseId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Warehouse',
      required: function() {
        return this.role === 'warehouse';
      },
    },
    isActive: {
      type: Boolean,
      default: true,
    },
  },
  {
    timestamps: true,
  }
);

// Method to compare entered password with stored hash
userSchema.methods.matchPassword = async function (enteredPassword) {
  return await bcrypt.compare(enteredPassword, this.password);
};

// Hash password before saving user
userSchema.pre('save', async function (next) {
  if (!this.isModified('password')) {
    next();
  }
  const salt = await bcrypt.genSalt(10);
  this.password = await bcrypt.hash(this.password, salt);
});

const User = mongoose.model('User', userSchema);
module.exports = User;

// File: server/models/Warehouse.js
// Description: Warehouse model for inventory management
const mongoose = require('mongoose');

const warehouseSchema = mongoose.Schema(
  {
    name: {
      type: String,
      required: true,
      unique: true,
    },
    address: {
      street: { type: String, required: true },
      city: { type: String, required: true },
      state: { type: String, required: true },
      pin: { type: String, required: true },
    },
    contactPerson: {
      type: String,
      required: true,
    },
    email: {
      type: String,
      required: true,
    },
    phone: {
      type: String,
      required: true,
    },
    isActive: {
      type: Boolean,
      default: true,
    },
  },
  {
    timestamps: true,
  }
);

const Warehouse = mongoose.model('Warehouse', warehouseSchema);
module.exports = Warehouse;

// File: server/models/Store.js
// Description: Store model for retail management
const mongoose = require('mongoose');

const storeSchema = mongoose.Schema(
  {
    name: {
      type: String,
      required: true,
      unique: true,
    },
    address: {
      street: { type: String, required: true },
      city: { type: String, required: true },
      state: { type: String, required: true },
      pin: { type: String, required: true },
    },
    pan: {
      type: String,
      required: true,
    },
    gst: {
      type: String,
      required: true,
    },
    contactPerson: {
      type: String,
      required: true,
    },
    email: {
      type: String,
      required: true,
    },
    phone: {
      type: String,
      required: true,
    },
    warehouseId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Warehouse',
      required: true,
    },
    isActive: {
      type: Boolean,
      default: true,
    },
  },
  {
    timestamps: true,
  }
);

const Store = mongoose.model('Store', storeSchema);
module.exports = Store;

// File: server/models/Device.js
// Description: Device model for inventory tracking
const mongoose = require('mongoose');

const deviceSchema = mongoose.Schema(
  {
    deviceId: {
      type: String,
      required: true,
      unique: true,
    },
    name: {
      type: String,
      required: true,
    },
    model: {
      type: String,
      required: true,
    },
    imei1: {
      type: String,
      required: true,
      unique: true,
    },
    imei2: {
      type: String,
      unique: true,
      sparse: true, // Allows null/undefined values to not trigger uniqueness constraint
    },
    storeName: {
      type: String,
    },
    status: {
      type: String,
      enum: ['in-warehouse', 'transferred', 'sold', 'returned', 'defective'],
      default: 'in-warehouse',
    },
    warehouseId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Warehouse',
      required: true,
    },
    storeId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Store',
    },
    dateOfPurchase: {
      type: Date,
    },
    dateOfSale: {
      type: Date,
    },
    onLoan: {
      type: Boolean,
      default: false,
    },
    testResults: {
      battery: {
        health: String,
        level: Number,
        tests: [{
          name: String,
          result: String
        }]
      },
      camera: {
        tests: [{
          name: String,
          result: String
        }]
      },
      display: {
        tests: [{
          name: String,
          result: String
        }]
      },
      audio: {
        tests: [{
          name: String,
          result: String
        }]
      },
      sensors: {
        tests: [{
          name: String,
          result: String
        }]
      },
      connectivity: {
        tests: [{
          name: String,
          result: String
        }]
      }
    },
    logs: [
      {
        action: {
          type: String,
          required: true,
        },
        performedBy: {
          type: mongoose.Schema.Types.ObjectId,
          ref: 'User',
          required: true,
        },
        timestamp: {
          type: Date,
          default: Date.now,
        },
        details: {
          type: String,
        },
      },
    ],
  },
  {
    timestamps: true,
  }
);

// Generate unique deviceId before saving
deviceSchema.pre('save', async function (next) {
  if (!this.deviceId) {
    // Generate a unique device ID based on model and timestamp
    const timestamp = Date.now().toString().slice(-6);
    const modelCode = this.model.replace(/[^a-zA-Z0-9]/g, '').slice(0, 4).toUpperCase();
    this.deviceId = `${modelCode}-${timestamp}`;
  }
  next();
});

const Device = mongoose.model('Device', deviceSchema);
module.exports = Device;

// File: server/models/EMI.js
// Description: EMI model for tracking device loans
const mongoose = require('mongoose');

const emiSchema = mongoose.Schema(
  {
    deviceId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'Device',
      required: true,
    },
    customerId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true,
    },
    totalAmount: {
      type: Number,
      required: true,
    },
    tenure: {
      type: Number, // In months
      required: true,
    },
    installments: [
      {
        amount: {
          type: Number,
          required: true,
        },
        dueDate: {
          type: Date,
          required: true,
        },
        paidDate: {
          type: Date,
        },
        status: {
          type: String,
          enum: ['pending', 'paid', 'overdue'],
          default: 'pending',
        },
      },
    ],
    status: {
      type: String,
      enum: ['active', 'completed', 'defaulted'],
      default: 'active',
    },
  },
  {
    timestamps: true,
  }
);

const EMI = mongoose.model('EMI', emiSchema);
module.exports = EMI;
