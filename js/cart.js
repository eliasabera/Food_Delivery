// Sample data for menu items
const menuItems = [
  {
    name: "Torta",
    price: 280,
    image: "images/doro.jpg",
    description: "Ethiopian rice cake for light breakfast",
    restaurant: "Restaurant A",
  },
  {
    name: "Burger",
    price: 450,
    image: "images/burger.jpg",
    description: "Delicious beef burger with fresh veggies",
    restaurant: "Restaurant B",
  },
  {
    name: "Pizza",
    price: 600,
    image: "images/pizza.jpg",
    description: "Cheesy pepperoni pizza",
    restaurant: "Restaurant C",
  },
  // Add more items here (total of 45 items for 3 pages)
];

// Cart data
let cart = [];
let cartTotal = 0;

// Function to display menu items for a specific page
function displayMenuItems(page) {
  const itemsPerPage = 15;
  const startIndex = (page - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const itemsToShow = menuItems.slice(startIndex, endIndex);

  const menuItemsContainer = document.getElementById("menu-items");
  menuItemsContainer.innerHTML = "";

  itemsToShow.forEach((item) => {
    const card = `
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow">
                    <img src="${item.image}" class="card-img-top" alt="${item.name}">
                    <div class="card-body">
                        <h3 class="card-title">${item.name}</h3>
                        <p class="card-text">${item.description}</p>
                        <p class="price h5">${item.price} Birr</p>
                        <p class="restaurant">Delivered by: ${item.restaurant}</p>
                        <button class="btn btn-primary w-100 add-to-cart" data-name="${item.name}" data-price="${item.price}">Add to Cart</button>
                    </div>
                </div>
            </div>
        `;
    menuItemsContainer.innerHTML += card;
  });

  // Add event listeners to "Add to Cart" buttons
  document.querySelectorAll(".add-to-cart").forEach((button) => {
    button.addEventListener("click", addToCart);
  });
}

// Function to generate pagination links
function generatePagination() {
  const totalPages = Math.ceil(menuItems.length / 15);
  const paginationContainer = document.getElementById("pagination");
  paginationContainer.innerHTML = "";

  for (let i = 1; i <= totalPages; i++) {
    const pageLink = `
            <li class="page-item"><a class="page-link" href="#" onclick="displayMenuItems(${i})">${i}</a></li>
        `;
    paginationContainer.innerHTML += pageLink;
  }
}

// Function to add an item to the cart
function addToCart(event) {
  const itemName = event.target.getAttribute("data-name");
  const itemPrice = parseFloat(event.target.getAttribute("data-price"));

  const existingItem = cart.find((item) => item.name === itemName);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({ name: itemName, price: itemPrice, quantity: 1 });
  }

  updateCart();
}

// Function to update the cart display
function updateCart() {
  const cartList = document.getElementById("cart-list");
  const cartCount = document.getElementById("cart-count");
  const cartTotalElement = document.getElementById("cart-total");

  cartList.innerHTML = "";
  cartTotal = 0;

  cart.forEach((item) => {
    const row = `
            <tr>
                <td>${item.name}</td>
                <td>${item.price} Birr</td>
                <td>${item.quantity}</td>
                <td>${item.price * item.quantity} Birr</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-item" data-name="${
                      item.name
                    }">Remove</button>
                </td>
            </tr>
        `;
    cartList.innerHTML += row;
    cartTotal += item.price * item.quantity;
  });

  cartCount.textContent = cart.length;
  cartTotalElement.textContent = cartTotal.toFixed(2);

  // Add event listeners to "Remove" buttons
  document.querySelectorAll(".remove-item").forEach((button) => {
    button.addEventListener("click", removeFromCart);
  });
}

// Function to remove an item from the cart
function removeFromCart(event) {
  const itemName = event.target.getAttribute("data-name");
  cart = cart.filter((item) => item.name !== itemName);
  updateCart();
}

// Function to handle checkout
function handleCheckout() {
  const totalQuantity = cart.reduce((total, item) => total + item.quantity, 0);
  const totalPrice = cartTotal;

  // Redirect to payment page with total price and quantity as URL parameters
  window.location.href = `payment.html?totalPrice=${totalPrice}&totalQuantity=${totalQuantity}`;
}

// Add event listener to the checkout button
document
  .getElementById("checkout-btn")
  .addEventListener("click", handleCheckout);

// Initial display
displayMenuItems(1);
generatePagination();
