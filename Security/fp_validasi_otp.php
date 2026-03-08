<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode - OTP</title>
    <style>
        /* CSS Styling - Konsisten dengan desain modern */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f7f6;
        }

        .auth-card {
            width: 90%;
            max-width: 400px;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .instruction-text {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95em;
        }

        .input-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 0.95em;
        }

        /* Styling untuk input OTP tunggal */
        .otp-input-single {
            width: 100%; /* Lebar penuh */
            padding: 12px;
            text-align: center; /* Teks di tengah */
            font-size: 1.5em; /* Ukuran font lebih besar */
            letter-spacing: 10px; /* Jarak antar karakter agar terlihat seperti kode */
            border: 2px solid #ccc;
            border-radius: 8px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
            outline: none;
        }

        .otp-input-single:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }
        
        /* Tombol Verifikasi */
        .btn-verify {
            width: 100%;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-verify:hover {
            background-color: #0056b3;
        }
        
        .resend-link {
            display: block;
            margin-top: 15px;
            font-size: 0.9em;
        }
        
        .resend-link a {
            color: #ff4500;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        
        <h2>VERIFIKASI KODE</h2>
        <p class="instruction-text">Silakan masukkan 6 digit kode yang telah dikirimkan ke email Anda.</p>
        
        <form method="POST" action="fp_proses_validasi_otp.php">
            
            <div class="input-group">
                <label for="otp">Kode Verifikasi (OTP)</label>
                
                <input type="text" id="otp" name="otp_code" 
                       class="otp-input-single" 
                       maxlength="6" 
                       placeholder="______" 
                       required>
            </div>
            
            <button type="submit" class="btn-verify">
                VERIFIKASI
            </button>
            
            <div class="resend-link">
                <a href="#">Kirim ulang kode</a>
            </div>
            
        </form>
        
    </div>

</body>
</html>