document.querySelector(".order-btn").addEventListener("click", () => {
    window.location.href="../pages/cart.html"
})
function toggleMenu() {
  const navLinks = document.getElementById("nav-links");
  navLinks.classList.toggle("show");
}
