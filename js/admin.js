document.addEventListener("DOMContentLoaded", function () {
  const navLinks = document.querySelectorAll(".nav-link");
  const sections = document.querySelectorAll(".section");

  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      // Remove active class from all links and sections
      navLinks.forEach((link) => link.classList.remove("active"));
      sections.forEach((section) => section.classList.remove("active"));

      // Add active class to clicked link and corresponding section
      this.classList.add("active");
      document.getElementById(this.dataset.section).classList.add("active");
    });
  });
});

// Modal functions
function showModal(id) {
  document.getElementById(id).style.display = "block";
}

function closeModal(id) {
  document.getElementById(id).style.display = "none";
}
