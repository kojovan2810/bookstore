<?php
include "../Src/config.php";
if($_SERVER['REQUEST_METHOD']=='POST'){

    $categoryName = $_POST['categoryName'];
    $categoryDescription = $_POST['categoryDescription'];
    $cek = $conn->query("SELECT * FROM kategori_produk WHERE nama_kategori = '$categoryName'");

    if($cek -> num_rows > 0){
       echo "<script>alert('The category already saved!');location.href='tambah_kategori.php';</script>";
    }else{
        $insert = $conn->query("INSERT INTO kategori_produk(nama_kategori, deskripsi) VALUES ('$categoryName', '$categoryDescription') ");
        echo "<script>alert('saved!');location.href='kategori.php';</script>";

    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - BukuBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --background: #f5f7fb;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .form-container {
            width: 100%;
            max-width: 800px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Header */
        .form-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 30px 40px;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-icon {
            font-size: 36px;
            background: rgba(255, 255, 255, 0.2);
            width: 70px;
            height: 70px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-text h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header-text p {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Form Body */
        .form-body {
            padding: 40px;
        }

        /* Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.2);
            color: var(--success);
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: var(--danger);
        }

        .alert i {
            font-size: 18px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }

        .form-group label .required {
            color: var(--danger);
            margin-left: 3px;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }

        .input-with-icon input,
        .input-with-icon textarea {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 15px;
            background-color: #f8f9fa;
            transition: all 0.3s;
            resize: vertical;
        }

        .input-with-icon textarea {
            min-height: 120px;
            padding-top: 14px;
        }

        .input-with-icon input:focus,
        .input-with-icon textarea:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .char-counter {
            font-size: 14px;
            color: var(--gray);
            text-align: right;
            margin-top: 5px;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid var(--light-gray);
        }

        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            border: none;
        }

        .btn-secondary {
            background-color: white;
            color: var(--gray);
            border: 1px solid var(--light-gray);
        }

        .btn-secondary:hover {
            background-color: #f8f9fa;
            border-color: var(--gray);
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--secondary));
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-container {
                max-width: 100%;
            }
            
            .form-header {
                padding: 25px 20px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .header-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
            
            .form-body {
                padding: 25px 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="header-text">
                    <h1>Add New Category</h1>
                    <p>Create a new book category to organize your collection</p>
                </div>
            </div>
        </div>

        <!-- Form Body -->
        <div class="form-body">
            <!-- PHP untuk menampilkan pesan error/sukses -->

            <form class="category-form" method="POST" action="">
                <!-- Nama Kategori -->
                <div class="form-group">
                    <label for="categoryName">
                        Category Name <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-tag"></i>
                        <input type="text" 
                               id="categoryName" 
                               name="categoryName" 
                               value=""
                               placeholder="Example: Fiction, Education, Business" 
                               required
                               maxlength="100">
                    </div>
                    <div class="char-counter">
                        Maximum 100 characters
                    </div>
                </div>
                
                <!-- Deskripsi Kategori -->
                <div class="form-group">
                    <label for="categoryDescription">
                        Description (Optional)
                    </label>
                    <div class="input-with-icon">
                        <i class="fas fa-align-left"></i>
                        <textarea id="categoryDescription" 
                                  name="categoryDescription" 
                                  placeholder="Brief description about this category (optional)"
                                  maxlength="500"></textarea>
                    </div>
                    <div class="char-counter">
                        Maximum 500 characters
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="kategori.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>