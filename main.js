
const API_BASE = "http://165.22.39.144/LAMPAPI/";

// -------------------- LOGIN PAGE --------------------
const loginForm = document.getElementById("login-form");
if (loginForm) {
  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = loginForm.username.value.trim();
    const password = loginForm.password.value.trim();

    const res = await fetch(API_BASE + "Login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password })
    });

    const data = await res.json();
    if (data.success) {
      localStorage.setItem("userId", data.userId);
      window.location.href = "dashboard.html";
    } else {
      alert("Login failed: " + data.error);
    }
  });
}

// -------------------- SIGNUP PAGE --------------------
const signupForm = document.getElementById("signup-form");
if (signupForm) {
  signupForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = signupForm.username.value.trim();
    const password = signupForm.password.value.trim();
    const email = signupForm.email.value.trim();

    const res = await fetch(API_BASE + "RegisterContact.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password, email })
    });

    const data = await res.json();
    if (data.success) {
      alert("Account created! Please login.");
      window.location.href = "index.html";
    } else {
      alert("Signup failed: " + data.error);
    }
  });
}

// -------------------- DASHBOARD PAGE --------------------
const searchForm = document.getElementById("search-form");
const contactsList = document.getElementById("contacts-list");
const addForm = document.getElementById("add-form");

async function loadContacts(query = "") {
  const userId = localStorage.getItem("userId");

  const res = await fetch(API_BASE + "SearchContact.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ search: query, userId })
  });

  const data = await res.json();

  contactsList.innerHTML = "";
  if (data.results && data.results.length > 0) {
    data.results.forEach((c) => {
      const div = document.createElement("div");
      div.className = "contact-item";
      div.innerHTML = `
        <p>
          <strong>${c.FirstName} ${c.LastName}</strong><br>
          Email: ${c.Email}<br>
          Phone: ${c.Phone}
        </p>
        <div class="contact-actions">
          <button class="login-btn edit-btn" data-id="${c.ID}">Edit</button>
          <button class="signup-btn delete-btn" data-id="${c.ID}">Delete</button>
        </div>
      `;
      contactsList.appendChild(div);
    });

    // attach events
    document.querySelectorAll(".edit-btn").forEach((btn) => {
      btn.addEventListener("click", () => editContactPrompt(btn.dataset.id));
    });

    document.querySelectorAll(".delete-btn").forEach((btn) => {
      btn.addEventListener("click", () => deleteContact(btn.dataset.id));
    });
  } else {
    contactsList.innerHTML = "<p>No contacts found.</p>";
  }
}

// Handle search
if (searchForm) {
  searchForm.addEventListener("submit", (e) => {
    e.preventDefault();
    loadContacts(document.getElementById("search-input").value.trim());
  });

  // Load all contacts on page load
  loadContacts();
}

// Handle add contact
if (addForm) {
  addForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const userId = localStorage.getItem("userId");
    const firstName = addForm.firstName.value.trim();
    const lastName = addForm.lastName.value.trim();
    const email = addForm.email.value.trim();
    const phone = addForm.phone.value.trim();

    const res = await fetch(API_BASE + "addContact.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ firstName, lastName, email, phone, userId })
    });

    const data = await res.json();
    if (data.success) {
      alert("Contact added!");
      addForm.reset();
      loadContacts(); // reload list
    } else {
      alert("Add failed: " + data.error);
    }
  });
}

// -------------------- EDIT CONTACT --------------------
async function editContactPrompt(contactId) {
  const newFirst = prompt("Enter new first name:");
  const newLast = prompt("Enter new last name:");
  const newEmail = prompt("Enter new email:");
  const newPhone = prompt("Enter new phone:");
  const userId = localStorage.getItem("userId");

  if (!newFirst || !newLast || !newEmail || !newPhone) {
    alert("Update cancelled (all fields required).");
    return;
  }

  const res = await fetch(API_BASE + "editContact.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      contactId,
      firstName: newFirst,
      lastName: newLast,
      email: newEmail,
      phone: newPhone,
      userId
    })
  });

  const data = await res.json();
  if (data.success) {
    alert("Contact updated!");
    loadContacts();
  } else {
    alert("Update failed: " + data.error);
  }
}

// -------------------- DELETE CONTACT --------------------
async function deleteContact(contactId) {
  if (!confirm("Are you sure you want to delete this contact?")) return;

  const res = await fetch(API_BASE + "deleteContact.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ contactId })
  });

  const data = await res.json();
  if (data.success) {
    alert("Contact deleted!");
    loadContacts();
  } else {
    alert("Delete failed: " + data.error);
  }
}
