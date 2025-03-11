// File: client/src/App.js
// Description: Main application component
import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { AuthProvider } from './context/AuthContext';
import PrivateRoute from './components/PrivateRoute';
import Header from './components/Header';
import Sidebar from './components/Sidebar';
import Dashboard from './pages/Dashboard';
import Login from './pages/Login';
import UserList from './pages/users/UserList';
import UserCreate from './pages/users/UserCreate';
import UserEdit from './pages/users/UserEdit';
import WarehouseList from './pages/warehouses/WarehouseList';
import WarehouseCreate from './pages/warehouses/WarehouseCreate';
import WarehouseDetails from './pages/warehouses/WarehouseDetails';
import StoreList from './pages/stores/StoreList';
import StoreCreate from './pages/stores/StoreCreate';
import StoreDetails from './pages/stores/StoreDetails';
import DeviceList from './pages/devices/DeviceList';
import DeviceCreate from './pages/devices/DeviceCreate';
import DeviceDetails from './pages/devices/DeviceDetails';
import Profile from './pages/Profile';
import NotFound from './pages/NotFound';

function App() {
  return (
    <AuthProvider>
      <Router>
        <div className="app-container">
          <ToastContainer position="top-right" autoClose={3000} />
          <Routes>
            <Route path="/login" element={<Login />} />
            
            <Route path="/" element={<PrivateRoute />}>
              <Route path="/" element={
                <>
                  <Header />
                  <div className="main-container">
                    <Sidebar />
                    <main className="content">
                      <Routes>
                        <Route path="/" element={<Dashboard />} />
                        <Route path="/users" element={<UserList />} />
                        <Route path="/users/create" element={<UserCreate />} />
                        <Route path="/users/:id" element={<UserEdit />} />
                        <Route path="/warehouses" element={<WarehouseList />} />
                        <Route path="/warehouses/create" element={<WarehouseCreate />} />
                        <Route path="/warehouses/:id" element={<WarehouseDetails />} />
                        <Route path="/stores" element={<StoreList />} />
                        <Route path="/stores/create" element={<StoreCreate />} />
                        <Route path="/stores/:id" element={<StoreDetails />} />
                        <Route path="/devices" element={<DeviceList />} />
                        <Route path="/devices/create" element={<DeviceCreate />} />
                        <Route path="/devices/:id" element={<DeviceDetails />} />
                        <Route path="/profile" element={<Profile />} />
                      </Routes>
                    </main>
                  </div>
                </>
              } />
            </Route>
            
            <Route path="*" element={<NotFound />} />
          </Routes>
        </div>
      </Router>
    </AuthProvider>
  );
}

export default App;

// File: client/src/context/AuthContext.js
// Description: Authentication context for state management
import React, { createContext, useState, useEffect } from 'react';
import { toast } from 'react-toastify';
import api from '../services/api';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('token') || null);
  const [loading, setLoading] = useState(true);

  // Set token in local storage and axios headers
  useEffect(() => {
    if (token) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      localStorage.setItem('token', token);
    } else {
      delete api.defaults.headers.common['Authorization'];
      localStorage.removeItem('token');
    }
  }, [token]);

  // Load user data on app init
  useEffect(() => {
    const loadUser = async () => {
      setLoading(true);
      if (token) {
        try {
          const res = await api.get('/api/auth/profile');
          setUser(res.data);
        } catch (err) {
          console.error('Failed to load user:', err);
          setToken(null);
          toast.error('Your session has expired. Please log in again.');
        }
      }
      setLoading(false);
    };

    loadUser();
  }, [token]);

  // Login user
  const login = async (email, password) => {
    try {
      const res = await api.post('/api/auth/login', { email, password });
      setToken(res.data.token);
      setUser(res.data);
      return true;
    } catch (err) {
      const message = err.response?.data?.message || 'Login failed';
      toast.error(message);
      return false;
    }
  };

  // Logout user
  const logout = () => {
    setToken(null);
    setUser(null);
  };

  // Update user profile
  const updateProfile = async (userData) => {
    try {
      const res = await api.put('/api/auth/profile', userData);
      setUser(res.data);
      setToken(res.data.token);
      toast.success('Profile updated successfully');
      return true;
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to update profile';
      toast.error(message);
      return false;
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        login,
        logout,
        updateProfile,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export default AuthContext;

// File: client/src/services/api.js
// Description: Axios instance for API requests
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || '',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add request interceptor to include auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor to handle errors
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Handle session expiration
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
    }
    
    return Promise.reject(error);
  }
);

export default api;

// File: client/src/components/Header.js
// Description: Header component with navigation and user menu
import React, { useContext } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import AuthContext from '../context/AuthContext';

const Header = () => {
  const { user, logout } = useContext(AuthContext);
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <header className="header">
      <div className="logo">
        <Link to="/">Secure App</Link>
      </div>
      
      <div className="user-menu">
        <div className="user-info">
          <span>{user?.name}</span>
          <small>{user?.role}</small>
        </div>
        
        <div className="dropdown">
          <button className="dropdown-toggle">
            <i className="fas fa-user-circle"></i>
          </button>
          
          <div className="dropdown-menu">
            <Link to="/profile" className="dropdown-item">
              <i className="fas fa-user"></i> Profile
            </Link>
            
            <button onClick={handleLogout} className="dropdown-item">
              <i className="fas fa-sign-out-alt"></i> Logout
            </button>
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;

// File: client/src/components/Sidebar.js
// Description: Sidebar navigation component with role-based menu items
import React, { useContext } from 'react';
import { NavLink } from 'react-router-dom';
import AuthContext from '../context/AuthContext';

const Sidebar = () => {
  const { user } = useContext(AuthContext);
  
  // Define menu items based on user role
  const getMenuItems = () => {
    const items = [];
    
    // Dashboard - available to all
    items.push({
      path: '/',
      icon: 'fas fa-tachometer-alt',
      label: 'Dashboard',
    });
    
    // Admin and SuperAdmin menus
    if (user?.role === 'admin' || user?.role === 'superadmin') {
      items.push({
        path: '/users',
        icon: 'fas fa-users',
        label: 'Users',
      });
      
      items.push({
        path: '/warehouses',
        icon: 'fas fa-warehouse',
        label: 'Warehouses',
      });
    }
    
    // Admin, SuperAdmin, and Warehouse menus
    if (['admin', 'superadmin', 'warehouse'].includes(user?.role)) {
      items.push({
        path: '/stores',
        icon: 'fas fa-store',
        label: 'Stores',
      });
    }
    
    // Available to all roles
    items.push({
      path: '/devices',
      icon: 'fas fa-mobile-alt',
      label: 'Devices',
    });
    
    return items;
  };
  
  const menuItems = getMenuItems();
  
  return (
    <aside className="sidebar">
      <nav className="sidebar-nav">
        <ul>
          {menuItems.map((item) => (
            <li key={item.path}>
              <NavLink
                to={item.path}
                className={({ isActive }) =>
                  isActive ? 'sidebar-link active' : 'sidebar-link'
                }
                end={item.path === '/'}
              >
                <i className={item.icon}></i>
                <span>{item.label}</span>
              </NavLink>
            </li>
          ))}
        </ul>
      </nav>
    </aside>
  );
};

export default Sidebar;

// File: client/src/components/PrivateRoute.js
// Description: Component to protect routes from unauthorized access
import React, { useContext } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import AuthContext from '../context/AuthContext';
import Spinner from './Spinner';

const PrivateRoute = () => {
  const { user, loading } = useContext(AuthContext);
  
  if (loading) {
    return <Spinner />;
  }
  
  return user ? <Outlet /> : <Navigate to="/login" />;
};

export default PrivateRoute;

// File: client/src/components/Spinner.js
// Description: Loading spinner component
import React from 'react';

const Spinner = () => {
  return (
    <div className="spinner-container">
      <div className="spinner"></div>
    </div>
  );
};

export default Spinner;

// File: client/src/pages/Login.js
// Description: Login page component
import React, { useState, useContext, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import AuthContext from '../context/AuthContext';

const Login = () => {
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });
  const [loading, setLoading] = useState(false);
  
  const { user, login } = useContext(AuthContext);
  const navigate = useNavigate();
  
  useEffect(() => {
    // Redirect if already logged in
    if (user) {
      navigate('/');
    }
  }, [user, navigate]);
  
  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    
    const { email, password } = formData;
    const success = await login(email, password);
    
    if (success) {
      navigate('/');
    }
    
    setLoading(false);
  };
  
  return (
    <div className="login-container">
      <div className="login-form-container">
        <div className="login-logo">
          <h1>Secure App</h1>
        </div>
        
        <form onSubmit={handleSubmit} className="login-form">
          <h2>Admin Login</h2>
          
          <div className="form-group">
            <label htmlFor="email">Email</label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
            />
          </div>
          
          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              value={formData.password}
              onChange={handleChange}
              required
            />
          </div>
          
          <button
            type="submit"
            className="btn btn-primary btn-block"
            disabled={loading}
          >
            {loading ? 'Logging in...' : 'Login'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default Login;

// File: client/src/pages/Dashboard.js
// Description: Dashboard page with key metrics and statistics
import React, { useState, useEffect, useContext } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import AuthContext from '../context/AuthContext';
import Spinner from '../components/Spinner';

const Dashboard = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const { user } = useContext(AuthContext);
  
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        
        // Get device statistics
        const deviceStats = await api.get('/api/devices/statistics');
        
        // Get additional stats based on role
        let warehouseCount = 0;
        let storeCount = 0;
        let userCount = 0;
        
        if (['admin', 'superadmin'].includes(user.role)) {
          const warehousesRes = await api.get('/api/warehouses');
          warehouseCount = warehousesRes.data.length;
          
          const usersRes = await api.get('/api/users');
          userCount = usersRes.data.length;
        }
        
        if (['admin', 'superadmin', 'warehouse'].includes(user.role)) {
          const storesRes = await api.get('/api/stores');
          storeCount = storesRes.data.length;
        }
        
        setStats({
          devices: deviceStats.data,
          warehouseCount,
          storeCount,
          userCount,
        });
        
        setLoading(false);
      } catch (err) {
        console.error('Error fetching dashboard data:', err);
        setError('Failed to load dashboard data');
        setLoading(false);
      }
    };
    
    fetchData();
  }, [user.role]);
  
  if (loading) return <Spinner />;
  
  if (error) {
    return (
      <div className="error-container">
        <h2>Error</h2>
        <p>{error}</p>
        <button onClick={() => window.location.reload()} className="btn btn-primary">
          Retry
        </button>
      </div>
    );
  }
  
  const { devices, warehouseCount, storeCount, userCount } = stats;
  
  return (
    <div className="dashboard">
      <h1>Dashboard</h1>
      
      <div className="stats-cards">
        <div className="stat-card">
          <div className="stat-icon">
            <i className="fas fa-mobile-alt"></i>
          </div>
          <div className="stat-content">
            <h3>Total Devices</h3>
            <p className="stat-value">{devices.counts.totalDevices}</p>
          </div>
        </div>
        
        <div className="stat-card">
          <div className="stat-icon">
            <i className="fas fa-store"></i>
          </div>
          <div className="stat-content">
            <h3>In Stock</h3>
            <p className="stat-value">{devices.counts.devicesTransferred}</p>
          </div>
        </div>
        
        <div className="stat-card">
          <div className="stat-icon">
            <i className="fas fa-shopping-cart"></i>
          </div>
          <div className="stat-content">
            <h3>Devices Sold</h3>
            <p className="stat-value">{devices.counts.devicesSold}</p>
          </div>
        </div>
        
        <div className="stat-card">
          <div className="stat-icon">
            <i className="fas fa-warehouse"></i>
          </div>
          <div className="stat-content">
            <h3>In Warehouse</h3>
            <p className="stat-value">{devices.counts.devicesInWarehouse}</p>
          </div>
        </div>
      </div>
      
      {/* Admin and SuperAdmin specific stats */}
      {['admin', 'superadmin'].includes(user.role) && (
        <div className="stats-cards">
          <div className="stat-card">
            <div className="stat-icon">
              <i className="fas fa-warehouse"></i>
            </div>
            <div className="stat-content">
              <h3>Warehouses</h3>
              <p className="stat-value">{warehouseCount}</p>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon">
              <i className="fas fa-store"></i>
            </div>
            <div className="stat-content">
              <h3>Stores</h3>
              <p className="stat-value">{storeCount}</p>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon">
              <i className="fas fa-users"></i>
            </div>
            <div className="stat-content">
              <h3>Users</h3>
              <p className="stat-value">{userCount}</p>
            </div>
          </div>
        </div>
      )}
      
      {/* Device model distribution */}
      <div className="dashboard-section">
        <div className="section-header">
          <h2>Device Models</h2>
          <Link to="/devices" className="btn btn-sm btn-outline">
            View All
          </Link>
        </div>
        
        <div className="model-distribution">
          {devices.modelCounts.slice(0, 5).map((model) => (
            <div key={model._id} className="model-item">
              <div className="model-name">{model._id}</div>
              <div className="model-count">{model.count}</div>
            </div>
          ))}
        </div>
      </div>
      
      {/* Store distribution */}
      {['admin', 'superadmin', 'warehouse'].includes(user.role) && (
        <div className="dashboard-section">
          <div className="section-header">
            <h2>Store Distribution</h2>
            <Link to="/stores" className="btn btn-sm btn-outline">
              View All
            </Link>
          </div>
          
          <div className="store-distribution">
            {devices.storeCounts.slice(0, 5).map((store) => (
              <div key={store._id} className="store-item">
                <div className="store-name">{store.name}</div>
                <div className="store-count">{store.count}</div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default Dashboard;

// File: client/src/index.css
// Description: Main CSS styles for the application
body {
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background-color: #f5f7fb;
  color: #333;
}

/* Layout */
.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-container {
  display: flex;
  flex: 1;
  padding-top: 60px; /* Header height */
}

.content {
  flex: 1;
  padding: 20px;
  margin-left: 250px; /* Sidebar width */
}

/* Header */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  background-color: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  z-index: 1000;
}

.logo a {
  color: #2c3e50;
  font-size: 24px;
  font-weight: bold;
  text-decoration: none;
}

.user-menu {
  display: flex;
  align-items: center;
}

.user-info {
  margin-right: 15px;
  text-align: right;
}

.user-info span {
  display: block;
  font-weight: 500;
}

.user-info small {
  color: #6c757d;
  text-transform: capitalize;
}

.dropdown {
  position: relative;
}

.dropdown-toggle {
  background: none;
  border: none;
  color: #333;
  cursor: pointer;
  font-size: 20px;
}

.dropdown-menu {
  position: absolute;
  right: 0;
  background-color: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: none;
  min-width: 160px;
  z-index: 1;
}

.dropdown:hover .dropdown-menu {
  display: block;
}

.dropdown-item {
  display: block;
  padding: 8px 16px;
  text-decoration: none;
  color: #333;
  background: none;
  border: none;
  text-align: left;
  width: 100%;
  cursor: pointer;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

/* Sidebar */
.sidebar {
  position: fixed;
  left: 0;
  top: 60px;
  bottom: 0;
  width: 250px;
  background-color: #2c3e50;
  color: #fff;
  overflow-y: auto;
  z-index: 900;
}

.sidebar-nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-link {
  display: flex;
  align-items: center;
  padding: 15px 20px;
  color: #ecf0f1;
  text-decoration: none;
  transition: all 0.3s;
}

.sidebar-link:hover, .sidebar-link.active {
  background-color: #34495e;
}

.sidebar-link i {
  margin-right: 10px;
  width: 20px;
  text-align: center;
}

/* Spinner */
.spinner-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  width: 100%;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Login page */
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f7fb;
}

.login-form-container {
  width: 400px;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.login-logo {
  text-align: center;
  padding: 30px 0;
  background-color: #2c3e50;
  color: #fff;
}

.login-form {
  padding: 30px;
}

.login-form h2 {
  margin-bottom: 30px;
  text-align: center;
  color: #2c3e50;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
}

/* Buttons */
.btn {
  padding: 10px 15px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s;
}

.btn-primary {
  background-color: #3498db;
  color: #fff;
}

.btn-primary:hover {
  background-color: #2980b9;
}

.btn-danger {
  background-color: #e74c3c;
  color: #fff;
}

.btn-danger:hover {
  background-color: #c0392b;
}

.btn-success {
  background-color: #2ecc71;
  color: #fff;
}

.btn-success:hover {
  background-color: #27ae60;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #3498db;
  color: #3498db;
}

.btn-outline:hover {
  background-color: #3498db;
  color: #fff;
}

.btn-block {
  display: block;
  width: 100%;
}

.btn-sm {
  padding: 5px 10px;
  font-size: 14px;
}

/* Dashboard */
.dashboard h1 {
  margin-bottom: 30px;
  color: #2c3e50;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  display: flex;
  align-items: center;
}

.stat-icon {
  background-color: #edf2f7;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
}

.stat-icon i {
  font-size: 24px;
  color: #3498db;
}

.stat-content h3 {
  margin: 0 0 5px;
  font-size: 16px;
  color: #6c757d;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  margin: 0;
  color: #2c3e50;
}

.dashboard-section {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 30px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0;
  font-size: 18px;
  color: #2c3e50;
}

.model-distribution, .store-distribution {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
}

.model-item, .store-item {
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 6px;
  text-align: center;
}

.model-name, .store-name {
  font-weight: 500;
  margin-bottom: 5px;
}

.model-count, .store-count {
  font-size: 18px;
  font-weight: bold;
  color: #3498db;
}

/* Tables */
.table-container {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 30px;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.table-header h2 {
  margin: 0;
  font-size: 20px;
  color: #2c3e50;
}

.table-toolbar {
  display: flex;
  gap: 10px;
}

.table-filters {
  display: flex;
  gap: 15px;
  padding: 15px 20px;
  background-color: #f8f9fa;
}

.filter-group {
  display: flex;
  align-items: center;
}

.filter-group label {
  margin-right: 8px;
  font-weight: 500;
}

.filter-group select, .filter-group input {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 15px 20px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

th {
  background-color: #f8f9fa;
  font-weight: 500;
  color: #6c757d;
}

tbody tr:hover {
  background-color: #f8f9fa;
}

.status-badge {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-active {
  background-color: #e3fcef;
  color: #2ecc71;
}

.status-inactive {
  background-color: #fdeeee;
  color: #e74c3c;
}

/* Forms */
.form-container {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 30px;
  margin-bottom: 30px;
}

.form-title {
  margin-top: 0;
  margin-bottom: 30px;
  font-size: 24px;
  color: #2c3e50;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.form-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 30px;
}

/* Error page */
.error-container {
  text-align: center;
  padding: 50px;
}

.error-container h2 {
  color: #e74c3c;
  margin-bottom: 20px;
}

.error-container p {
  margin-bottom: 30px;
}
