document.addEventListener("DOMContentLoaded", function () {
  // Filter orders by status
  const filterButtons = document.querySelectorAll(".btn-filter");
  const orderCards = document.querySelectorAll(".order-card");

  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Remove active class from all buttons
      filterButtons.forEach((btn) => btn.classList.remove("active"));

      // Add active class to clicked button
      this.classList.add("active");

      const filter = this.dataset.filter;

      // Show/hide orders based on filter
      orderCards.forEach((card) => {
        if (filter === "all" || card.dataset.status === filter) {
          card.style.display = "block";
        } else {
          card.style.display = "none";
        }
      });
    });
  });

  // Cancel order functionality
  const cancelButtons = document.querySelectorAll(".btn-cancel");

  cancelButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const orderId = this.dataset.orderId;

      if (confirm("Are you sure you want to cancel this order?")) {
        // Send AJAX request to cancel order
        fetch("cancel_order.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `order_id=${orderId}`,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Reload page to show updated status
              window.location.reload();
            } else {
              alert("Error: " + data.message);
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            alert("An error occurred while cancelling the order");
          });
      }
    });
  });
});
