<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Generate & View Barcode ID</title>
  <script src="https://cdn.jsdelivr.net/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      padding-top: 50px;
      background-color: #f4f4f4;
    }
    input, button {
      padding: 10px;
      margin: 10px;
      font-size: 16px;
    }
    #barcodeBox {
      margin-top: 30px;
      background: white;
      padding: 20px;
      display: inline-block;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    #barcodeID {
      margin-top: 10px;
      font-weight: bold;
      font-size: 18px;
    }
  </style>
</head>
<body>

  <h2>Generate Student Barcode</h2>

  <input type="text" id="studentID" placeholder="Enter Student ID" />
  <button onclick="generateBarcode()">Generate Barcode</button>

  <div id="barcodeBox">
    <svg id="barcode"></svg>
    <div id="barcodeID"></div>
  </div>

  <script>
    function generateBarcode() {
      const id = document.getElementById("studentID").value;
      if (!id.trim()) {
        alert("Please enter a valid Student ID.");
        return;
      }

      // Generate the barcode
      JsBarcode("#barcode", id, {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 100,
        displayValue: false
      });

      // Display the ID below the barcode
      document.getElementById("barcodeID").textContent = "Student ID: " + id;
    }
  </script>

</body>
</html>