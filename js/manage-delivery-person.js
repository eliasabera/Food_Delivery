// Sample data for delivery persons
let deliveryPersons = [
  {
    username: "JohnDoe",
    email: "johndoe@example.com",
    phoneNumber: "+1234567890",
    address: "123 Main St, City",
    password: "password123",
  },
];

// Function to add a delivery person
function addDeliveryPerson(event) {
  event.preventDefault();

  const username = document.getElementById("username").value;
  const email = document.getElementById("emailid").value;
  const phoneNumber = document.getElementById("phonenumber").value;
  const address = document.getElementById("address").value;
  const password = document.getElementById("password").value;

  const newDeliveryPerson = {
    username,
    email,
    phoneNumber,
    address,
    password,
  };

  deliveryPersons.push(newDeliveryPerson);
  updateDeliveryList();
  document.getElementById("deliveryForm").reset();
}

// Function to update the delivery persons list
function updateDeliveryList() {
  const deliveryList = document.getElementById("delivery-list");
  deliveryList.innerHTML = "";

  deliveryPersons.forEach((person, index) => {
    const row = `
            <tr>
                <td>${person.username}</td>
                <td>${person.email}</td>
                <td>${person.phoneNumber}</td>
                <td>${person.address}</td>
                <td>
                    <button class="btn btn-sm btn-warning" onclick="editDeliveryPerson(${index})">Update</button>
                    <button class="btn btn-sm btn-danger" onclick="removeDeliveryPerson(${index})">Remove</button>
                </td>
            </tr>
        `;
    deliveryList.innerHTML += row;
  });
}

// Function to edit a delivery person
function editDeliveryPerson(index) {
  const person = deliveryPersons[index];
  document.getElementById("username").value = person.username;
  document.getElementById("emailid").value = person.email;
  document.getElementById("phonenumber").value = person.phoneNumber;
  document.getElementById("address").value = person.address;
  document.getElementById("password").value = person.password;

  // Remove the person from the list after editing
  deliveryPersons.splice(index, 1);
  updateDeliveryList();
}

// Function to remove a delivery person
function removeDeliveryPerson(index) {
  deliveryPersons.splice(index, 1);
  updateDeliveryList();
}

// Add event listener to the form
document
  .getElementById("deliveryForm")
  .addEventListener("submit", addDeliveryPerson);

// Initial display
updateDeliveryList();
