<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Information | SCMCSTI</title>
    <!-- Firebase Libraries -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>

    <!-- Simple CSS Styling -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #a8edea, #fed6e3);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            width: 350px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Student Information</h2>
    <form id="studentForm">
        <label for="studentID">Student ID</label>
        <input type="text" id="studentID" required>

        <label for="fullName">Full Name</label>
        <input type="text" id="fullName" required>

        <label for="year Level">Year Level</label>
        <input type="text" id="YearlevelLevel" required>

        <label for="course">Course</label>
        <input type="text" id="course" required>

        <label for="barcode">Barcode Number</label>
        <input type="text" id="barcode" required>

        <button type="submit">Save Student</button>
    </form>
</div>

<script>
    // Firebase Config (replace with your real config)
    const firebaseConfig = {
        apiKey: "YOUR_API_KEY",
        authDomain: "YOUR_AUTH_DOMAIN",
        databaseURL: "YOUR_DATABASE_URL",
        projectId: "YOUR_PROJECT_ID",
        storageBucket: "YOUR_STORAGE_BUCKET",
        messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
        appId: "YOUR_APP_ID"
    };

    // Initialize Firebase
    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();

    // Submit form
    document.getElementById("studentForm").addEventListener("submit", function (e) {
        e.preventDefault();

        const studentID = document.getElementById("studentID").value.trim();
        const fullName = document.getElementById("fullName").value.trim();
        const gradeLevel = document.getElementById("yearLevel").value.trim();
        const section = document.getElementById("course").value.trim();
        const barcode = document.getElementById("barcode").value.trim();

        // Save to Firebase
        database.ref('students/' + studentID).set({
            fullName: fullName,
            yearLevel: yearLevel,
            course: course,
            barcode: barcode
        }).then(() => {
            alert("Student information saved successfully!");
            document.getElementById("studentForm").reset();
        }).catch((error) => {
            alert("Error: " + error.message);
        });
    });
</script>

</body>
</html>
