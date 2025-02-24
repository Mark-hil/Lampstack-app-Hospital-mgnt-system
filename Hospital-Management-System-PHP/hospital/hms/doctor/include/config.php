<?php 
// Get database connection details from environment variables
$DB_SERVER = getenv('DB_SERVER');
$DB_NAME = getenv('DB_NAME');
$DB_USER = getenv('DB_USER');
$DB_PASS = getenv('DB_PASS');

// First connect without specifying a database
$temp_con = mysqli_connect($DB_SERVER, $DB_USER, $DB_PASS);
if (!$temp_con) {
    die("Failed to connect to MySQL server: " . mysqli_connect_error());
}

// Create database if it doesn't exist
if (!mysqli_query($temp_con, "CREATE DATABASE IF NOT EXISTS $DB_NAME")) {
    die("Failed to create database: " . mysqli_error($temp_con));
}

echo "Database $DB_NAME created/verified successfully.<br>";

// Select the database
mysqli_select_db($temp_con, $DB_NAME);

// Check if tblpage table exists (as a test for whether import has happened)
$result = mysqli_query($temp_con, "SHOW TABLES LIKE 'tblpage'");
if (mysqli_num_rows($result) == 0) {
    echo "Table 'tblpage' not found. Attempting to import SQL...<br>";
    
    // Define possible SQL file locations
    $sql_file = dirname(__FILE__) . '/hms.sql';
    $zip_file = dirname(__FILE__) . '/hms.zip';
    
    // Try to import from SQL file
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Split SQL by semicolons while preserving them in the statements
        $statements = preg_split('/;\s*$/m', $sql);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                // Add back the semicolon
                $statement .= ';';
                
                if (mysqli_query($temp_con, $statement)) {
                    $success_count++;
                } else {
                    $error_count++;
                    // Only show first few errors to avoid flooding the screen
                    if ($error_count <= 3) {
                        echo "Error executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...<br>";
                        echo "MySQL Error: " . mysqli_error($temp_con) . "<br>";
                    }
                }
            }
        }
        
        echo "Import completed with $success_count successful queries and $error_count errors.<br>";
    } 
    // Try to extract and import from ZIP file
    else if (file_exists($zip_file) && extension_loaded('zip')) {
        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            // Find the SQL file in the ZIP
            $found_sql = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                    echo "Found SQL file in ZIP: " . $filename . "<br>";
                    $sql = $zip->getFromIndex($i);
                    
                    // Execute the SQL commands
                    $statements = preg_split('/;\s*$/m', $sql);
                    
                    $success_count = 0;
                    $error_count = 0;
                    
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement)) {
                            // Add back the semicolon
                            $statement .= ';';
                            
                            if (mysqli_query($temp_con, $statement)) {
                                $success_count++;
                            } else {
                                $error_count++;
                                // Only show first few errors
                                if ($error_count <= 3) {
                                    echo "Error executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...<br>";
                                    echo "MySQL Error: " . mysqli_error($temp_con) . "<br>";
                                }
                            }
                        }
                    }
                    
                    echo "Import completed with $success_count successful queries and $error_count errors.<br>";
                    $found_sql = true;
                    break;
                }
            }
            
            if (!$found_sql) {
                echo "No SQL file found in the ZIP archive.<br>";
            }
            
            $zip->close();
        } else {
            echo "Failed to open ZIP file.<br>";
        }
    } else {
        echo "Neither SQL file nor ZIP file found. Please check file paths.<br>";
    }
    
    // Verify if the table was created
    $result = mysqli_query($temp_con, "SHOW TABLES LIKE 'tblpage'");
    if (mysqli_num_rows($result) == 0) {
        echo "<strong>Warning: Table 'tblpage' still not found after import attempt.</strong><br>";
    } else {
        echo "Table 'tblpage' verified successfully.<br>";
    }
} else {
    echo "Table 'tblpage' already exists. Database appears to be set up correctly.<br>";
}

mysqli_close($temp_con);

// Set up the main connection variable for the application
$con = mysqli_connect($DB_SERVER, $DB_USER, $DB_PASS, $DB_NAME);
if (!$con) {
    die("Failed to connect to MySQL database: " . mysqli_connect_error());
}

echo "Database connection established successfully.<br>";
?>