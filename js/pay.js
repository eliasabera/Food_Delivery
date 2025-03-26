// Function to retrieve URL parameters
function getUrlParams() {
  const urlParams = new URLSearchParams(window.location.search);
  return {
    totalPrice: urlParams.get("totalPrice"),
    totalQuantity: urlParams.get("totalQuantity"),
  };
}

// Function to update the payment details on the page
function updatePaymentDetails() {
  const { totalPrice, totalQuantity } = getUrlParams();

  // Update total items and total amount
  const totalItemsElement = document.querySelector(
    ".amount-box p:nth-child(2) span"
  );
  const totalAmountElement = document.querySelector(
    ".amount-box p:nth-child(3) span"
  );

  if (totalItemsElement && totalAmountElement) {
    totalItemsElement.textContent = totalQuantity || "0";
    totalAmountElement.textContent = `${totalPrice || "0.00"}birr`;
  }
}

// Function to handle the "Pay Now" button click
function handlePayNow() {
  const phoneInput = document.getElementById("phone");
  const phoneNumber = phoneInput.value.trim();

  if (!phoneNumber) {
    alert("Please enter your phone number.");
    return;
  }

  // Simulate payment processing
  alert(
    `Payment of ${
      getUrlParams().totalPrice
    } is being processed for phone number: ${phoneNumber}`
  );
  // Redirect to a thank you page or home page after payment
  window.location.href = "thank-you.html";
}

// Add event listener to the "Pay Now" button
document.querySelector(".pay-btn").addEventListener("click", handlePayNow);

// Update payment details when the page loads
document.addEventListener("DOMContentLoaded", updatePaymentDetails);
