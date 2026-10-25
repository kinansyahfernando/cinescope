<?php
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // Remove whitespace
    $data = trim($data);
    
    // Remove backslashes
    $data = stripslashes($data);
    
    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

function validateInteger($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    
    if ($value === false) {
        return false;
    }
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return $value;
}

function validateFloat($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_VALIDATE_FLOAT);
    
    if ($value === false) {
        return false;
    }
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return $value;
}
?>