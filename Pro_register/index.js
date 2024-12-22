import express from 'express';
import multer from 'multer';
import bodyParser from 'body-parser';
import { initializeApp } from 'firebase/app';
import { getDatabase, ref, set } from 'firebase/database';
import { getStorage, ref as storageRef, uploadBytes } from 'firebase/storage';
import bcrypt from 'bcrypt';
import path from 'path';

// Firebase Configuration
const firebaseConfig = {
  apiKey: "AIzaSyBO2zepKrSHmEu4uA4Plo8yQ0IBMVxj85g",
  authDomain: "bettergrassnow-28a94.firebaseapp.com",
  databaseURL: "https://bettergrassnow-28a94-default-rtdb.firebaseio.com",
  projectId: "bettergrassnow-28a94",
  storageBucket: "bettergrassnow-28a94.appspot.com",
  messagingSenderId: "995707952744",
  appId: "1:995707952744:web:5f16c23969015bb9f4e5dd",
  measurementId: "G-N1D0WCXEXL"
};

// Initialize Firebase
const firebaseApp = initializeApp(firebaseConfig);
const database = getDatabase(firebaseApp);
const storage = getStorage(firebaseApp);

// Express app setup
const app = express();
const port = 3000;

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Static folder for serving files
app.use(express.static(path.join(__dirname, 'public')));

// Multer setup for file uploads
const upload = multer({ storage: multer.memoryStorage() });

// Helper functions
const hashPassword = async (password) => {
  const saltRounds = 10;
  return await bcrypt.hash(password, saltRounds);
};

// Route: Display registration form
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

// Route: Handle form submission
app.post('/register', upload.single('image'), async (req, res) => {
  try {
    const {
      email, password, password_confirmation, fname, lname, address,
      city, state, zip, social, dob, stripe, background_check
    } = req.body;

    // Validate form fields
    if (!email || !password || !password_confirmation || !fname || !lname ||
      !address || !city || !state || !zip || !stripe || !background_check) {
      return res.status(400).send('All required fields must be filled.');
    }
    if (password !== password_confirmation) {
      return res.status(400).send('Passwords do not match.');
    }
    if (password.length < 8 || !/\d/.test(password) || !/[A-Za-z]/.test(password)) {
      return res.status(400).send('Password must be at least 8 characters long and include both letters and numbers.');
    }

    // Hash passwords
    const hashedPassword = await hashPassword(password);
    const hashedPasswordConfirmation = await hashPassword(password_confirmation);

    // Handle file upload
    let imageURL = '';
    if (req.file) {
      const fileBuffer = req.file.buffer;
      const storagePath = `provider_images/${Date.now()}-${req.file.originalname}`;
      const storageReference = storageRef(storage, storagePath);

      const uploadResult = await uploadBytes(storageReference, fileBuffer);
      imageURL = `https://firebasestorage.googleapis.com/v0/b/${firebaseConfig.storageBucket}/o/${encodeURIComponent(storagePath)}?alt=media`;
    } else {
      return res.status(400).send('Profile picture is required.');
    }

    // Save data to Firebase Realtime Database
    const providerId = `provider_${Date.now()}`;
    const providerRef = ref(database, `providers/${providerId}`);
    const providerData = {
      email,
      password: hashedPassword,
      fname,
      lname,
      address,
      city,
      state,
      zip,
      social: social || null,
      dob: dob || null,
      stripe,
      image: imageURL,
      background_check: Boolean(background_check),
      password_confirmation: hashedPasswordConfirmation
    };

    await set(providerRef, providerData);

    // Redirect to Stripe payment URL
    const stripePaymentUrl = `https://buy.stripe.com/00g3cRcY9c4laGcaEG?success_url=${encodeURIComponent('http://yourdomain.com/Pro_login/Pro_login.html')}`;
    res.redirect(stripePaymentUrl);
  } catch (error) {
    console.error('Error registering provider:', error);
    res.status(500).send('An error occurred during registration.');
  }
});

// Start server
app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});
