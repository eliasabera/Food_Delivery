<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SignUp</title>
    <link rel="stylesheet" href="../css/signup.css" />
  </head>
  <body>
    <div class="signup">
      <h3>Register</h3>
      <form class="form" action="../php/register.php" method="POST">
        <div class="form-group">
          <label for="username">Username:</label>
          <input
            type="text"
            name="username"
            id="username"
            pattern="^[a-zA-Z0-9]{3,15}$"
            title="Username must be 3 to 15 characters long and contain only letters and numbers."
            placeholder="Enter your username"
            required
          />
        </div>

        <div class="form-group">
          <label for="emailid">Email ID:</label>
          <input
            type="email"
            name="emailid"
            id="emailid"
            pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
            title="Please enter a valid email address."
            placeholder="Enter your email address"
            required
          />
        </div>

        <div class="form-group">
          <label for="phonenumber">Phone number:</label>
          <input
            type="text"
            name="phonenumber"
            id="phonenumber"
            pattern="^(09\d{8}|\+251\d{9})$"
            title="Phone number must start with 09 or +251 and be followed by 8 digits."
            placeholder="Enter your phone number"
            required
          />
        </div>

        <div class="form-group">
          <label for="address">Address:</label>
          <input
            type="text"
            name="address"
            id="address"
            pattern="^[a-zA-Z0-9\s,.'-]{3,}$"
            title="Please enter a valid street address or location."
            placeholder="Enter your street address or specific location"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input
            type="password"
            name="password"
            id="password"
            pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$"
            title="Password must be at least 6 characters long, including both letters and numbers."
            placeholder="Enter your password"
            required
          />
        </div>

        <button type="submit">Register Now</button>
        <p>Have an account? <a href="login.php">Login</a></p>
      </form>
    </div>

    <script src="../js/signup.js"></script>
  </body>
</html>