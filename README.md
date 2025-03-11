## How to Use the System

1. **Initial Setup**
   - Set up MongoDB database
   - Configure environment variables
   - Install dependencies and start the application
   - Use default SuperAdmin credentials to log in

2. **Creating Users**
   - SuperAdmin can create Admin users
   - Admin can create Warehouse administrators
   - Warehouse administrators can create Store users

3. **Managing Inventory**
   - Warehouse users can add devices to inventory
   - Devices can be transferred to stores
   - Store users can mark devices as sold or return them to the warehouse
   - All actions are logged for accountability

4. **Device Lifecycle**
   - Devices start in the warehouse after testing
   - Devices are transferred to stores
   - Stores sell devices to customers
   - Devices can be returned from stores to warehouse

## Implementation Instructions

### 1. Set Up the Development Environment

1. Install Node.js and MongoDB on your system
2. Create project folders for client and server
3. Initialize the project with package.json files

### 2. Backend Setup

1. Copy the backend code into the appropriate files
2. Install dependencies:
   ```
   npm install express mongoose bcryptjs jsonwebtoken cors dotenv morgan express-async-handler
   ```
3. Create a `.env` file with your configuration

### 3. Frontend Setup

1. Create a React application:
   ```
   npx create-react-app client
   ```
2. Install frontend dependencies:
   ```
   npm install react-router-dom axios react-toastify
   ```
3. Copy the frontend code into the appropriate files
4. Add Font Awesome for icons

### 4. Running the Application

1. Start MongoDB
2. Start the backend server:
   ```
   cd server
   npm start
   ```
3. Start the React frontend:
   ```
   cd client
   npm start
   ```
4. Access the admin interface at http://localhost:3000

The first user created will be the SuperAdmin with the credentials specified in your .env file. You can then use this account to create additional users, warehouses, and stores.
