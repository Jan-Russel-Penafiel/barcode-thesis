<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Barcode Generator</title>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #e6f2ff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .form-container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      width: 350px;
    }

    h2 {
      text-align: center;
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
      background-color: #007BFF;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }

    button:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>

<div class="form-container">
  <h2>Student Barcode Generator</h2>
  <label for="studentID">Student ID</label>
  <input type="text" id="studentID" placeholder="e.g. 2025-001" required>

  <label for="course">Course</label>
  <input type="text" id="course" placeholder="e.g. BSIT" required>

  <label for="year">Year</label>
  <input type="text" id="year" placeholder="e.g. 3rd Year" required>

  <button onclick="generateBarcode()">Generate Barcode</button>
</div>

<script>
  function generateBarcode() {
    const studentID = document.getElementById("studentID").value.trim();
    const course = document.getElementById("course").value.trim();
    const year = document.getElementById("year").value.trim();

    if (!studentID || !course || !year) {
      alert("Please fill out all fields.");
      return;
    }

    const popup = window.open('', 'BarcodePopup', 'width=450,height=400');
    popup.document.write(`
      <html>
      <head>
        <title>Student Barcode</title>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
      </head>
      <body style="font-family: Arial; text-align: center; padding-top: 40px;">
        <h3>Course: ${course}</h3>
        <h4>Year: ${year}</h4>
        <svg id="barcode"></svg>
        <p>Student ID: ${studentID}</p>
        <script>
          JsBarcode("#barcode", "${studentID}", {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 100,
            displayValue: true
          });
        <\/script>
      </body>
      </html>
    `);
    popup.document.close();
  }
</script>

</body>
</html>