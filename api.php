<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Barcode - Barcode Attendance</title>
    <script src="https://cdn.jsdelivr.net/npm/react@18.2.0/umd/react.development.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/react-dom@18.2.0/umd/react-dom.development.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/babel-standalone@7.22.9/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.4.0/dist/axios.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div id="root"></div>
    <script type="text/babel">
        function ScanBarcode() {
            const [result, setResult] = React.useState('Scanner will start automatically...');
            const scannerRef = React.useRef(null);

            React.useEffect(() => {
                // Check if user is logged in
                axios.get('/api.php?action=check_session')
                    .then(response => {
                        if (!response.data.loggedin) {
                            window.location.href = 'login.php';
                        } else {
                            startScanner();
                        }
                    })
                    .catch(() => {
                        window.location.href = 'login.php';
                    });

                return () => {
                    if (scannerRef.current) {
                        scannerRef.current.stop().catch(() => {});
                    }
                };
            }, []);

            const startScanner = () => {
                scannerRef.current = new Html5Qrcode("scanner-container");
                scannerRef.current.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        axios.post('/api.php?action=log_attendance', { barcode: decodedText }, {
                            headers: { 'Content-Type': 'application/json' }
                        })
                        .then(response => {
                            setResult(response.data.message);
                        })
                        .catch(() => {
                            setResult('Error logging attendance');
                        });
                    },
                    (error) => {
                        // Ignore scan errors (e.g., no barcode detected)
                    }
                )
                .catch(() => {
                    setResult('Error starting scanner. Please check camera permissions.');
                });
            };

            return (
                <div className="container mx-auto p-4 max-w-md">
                    <h2 className="text-2xl font-bold text-gray-800 mb-6 text-center">Scan Barcode</h2>
                    <a
                        href="index.php"
                        className="inline-block mb-4 text-blue-500 hover:underline"
                    >
                        Back to Dashboard
                    </a>
                    <div id="scanner-container" className="w-full h-64 border border-gray-300 rounded-lg mb-4"></div>
                    <p className="text-center text-gray-600">{result}</p>
                </div>
            );
        }

        ReactDOM.render(<ScanBarcode />, document.getElementById('root'));
    </script>
</body>
</html>