<?php
include("database_connectivity.php");

$errors = [];
$values = [
    'name' => '',
    'phone' => '',
    'address' => '',
    'email' => '',
    'gender' => ''
];
$details = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $val) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    // Name: only alphabets, at least 2 characters
    if (empty($values['name'])) {
        $errors['name'] = "Name is required.";
    } elseif (!preg_match('/^[A-Za-z\s]{2,}$/', $values['name'])) {
        $errors['name'] = "Name must contain only alphabets and be at least 2 characters.";
    }

    // Phone: exactly 10 digits
    if (empty($values['phone'])) {
        $errors['phone'] = "Phone number is required.";
    } elseif (!preg_match('/^\d{10}$/', $values['phone'])) {
        $errors['phone'] = "Phone must be exactly 10 digits.";
    }

    // Address: realistic format
    if (empty($values['address'])) {
        $errors['address'] = "Address is required.";
    } elseif (!preg_match('/^[A-Za-z0-9\s,.\-\/#]{5,100}$/', $values['address'])) {
        $errors['address'] = "Address must be 5–100 characters and contain only letters, numbers, spaces, and , . - / #";
    }

    // Email: valid format
    if (empty($values['email'])) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Gender: must be one of the options
    if (!in_array($values['gender'], ['Male', 'Female', 'Other'])) {
        $errors['gender'] = "Please select a valid gender.";
    }

    // If no errors, insert into DB
    if (empty($errors)) {
        $custid = "CUST" . time() . rand(100, 999);

        $stmt = $conn->prepare("INSERT INTO customer_det (Cust_id, Name, Phno, Address, Email, Gender) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $custid, $values['name'], $values['phone'], $values['address'], $values['email'], $values['gender']);

        if ($stmt->execute()) {
            $message = "✅ Registration successful!";
            $details = "
                <strong>Customer ID:</strong> $custid<br>
                <strong>Name:</strong> {$values['name']}<br>
                <strong>Phone:</strong> {$values['phone']}<br>
                <strong>Address:</strong> {$values['address']}<br>
                <strong>Email:</strong> {$values['email']}<br>
                <strong>Gender:</strong> {$values['gender']}
            ";
            $values = array_fill_keys(array_keys($values), ''); // Clear form
        } else {
            $message = "⚠️ Something went wrong. Please try again.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  <style>
    body {
      background-image: url("../images/backgrounds/back4.png");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Arial, sans-serif;
    }

    .form-container {
      background-color: rgba(255, 255, 255, 0.9);
      padding: 20px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin: 10px 0 5px;
    }

    input, select {
      width: 100%;
      padding: 10px;
      margin-bottom: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    .error {
      color: red;
      font-size: 13px;
      margin-bottom: 10px;
    }

    .submit-btn {
      width: 100%;
      padding: 10px;
      background-color: #ff6b81;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .submit-btn:hover {
      background-color: #f89fac;
    }

    .message {
      margin-bottom: 10px;
      font-weight: bold;
      color: #2e7d32;
      text-align: center;
    }

    .output {
      margin-top: 15px;
      padding: 10px;
      background: #eaffea;
      border-radius: 5px;
      font-size: 14px;
      display: <?php echo $details ? 'block' : 'none'; ?>;
    }
  </style>
</head>
<body>

  <div class="form-container">
    <h2>Register</h2>

    <?php if ($message): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($values['name']); ?>">
      <?php if (isset($errors['name'])) echo "<div class='error'>{$errors['name']}</div>"; ?>

      <label for="phone">Phone</label>
      <input type="tel" id="phone" name="phone" maxlength="10" value="<?php echo htmlspecialchars($values['phone']); ?>">
      <?php if (isset($errors['phone'])) echo "<div class='error'>{$errors['phone']}</div>"; ?>

      <label for="address">Address</label>
      <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($values['address']); ?>">
      <?php if (isset($errors['address'])) echo "<div class='error'>{$errors['address']}</div>"; ?>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>">
      <?php if (isset($errors['email'])) echo "<div class='error'>{$errors['email']}</div>"; ?>

      <label for="gender">Gender</label>
      <select id="gender" name="gender">
        <option value="">Select</option>
        <option <?php if ($values['gender'] === 'Male') echo 'selected'; ?>>Male</option>
        <option <?php if ($values['gender'] === 'Female') echo 'selected'; ?>>Female</option>
        <option <?php if ($values['gender'] === 'Other') echo 'selected'; ?>>Other</option>
      </select>
      <?php if (isset($errors['gender'])) echo "<div class='error'>{$errors['gender']}</div>"; ?>

      <button type="submit" class="submit-btn">Submit</button>
    </form>

    <div class="output"><?php echo $details; ?></div>
  </div>

</body>
</html>

<?php $conn->close(); ?>