import { authenticateUser, registerUser } from "./data-service.js";
import { getCurrentUser, setCurrentUser } from "./state.js";
import { setFieldErrors } from "./ui.js";

export function initLoginPage() {
  const form = document.getElementById("login-form");
  if (!form) {
    return;
  }

  const currentUser = getCurrentUser();
  if (currentUser) {
    window.location.href = currentUser.role === "admin" ? "admin.html" : "profile.html";
    return;
  }

  const message = document.getElementById("login-message");

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    message.textContent = "";

    const formData = new FormData(form);
    const values = Object.fromEntries(formData.entries());
    const errors = validateLogin(values);
    setFieldErrors(form, errors);

    if (Object.keys(errors).length) {
      return;
    }

    const user = await authenticateUser(values.email, values.password);
    if (!user) {
      message.textContent = "Не удалось выполнить вход. Проверьте email и пароль.";
      return;
    }

    setCurrentUser(user);
    window.location.href = user.role === "admin" ? "admin.html" : "profile.html";
  });
}

export function initRegisterPage() {
  const form = document.getElementById("register-form");
  if (!form) {
    return;
  }

  const currentUser = getCurrentUser();
  if (currentUser) {
    window.location.href = currentUser.role === "admin" ? "admin.html" : "profile.html";
    return;
  }

  const message = document.getElementById("register-message");

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    message.textContent = "";

    const formData = new FormData(form);
    const values = Object.fromEntries(formData.entries());
    const errors = validateRegister(values);
    setFieldErrors(form, errors);

    if (Object.keys(errors).length) {
      return;
    }

    try {
      const user = await registerUser(values);
      setCurrentUser(user);
      message.textContent = "Регистрация прошла успешно. Перенаправляем в личный кабинет...";
      window.setTimeout(() => {
        window.location.href = "profile.html";
      }, 700);
    } catch (error) {
      message.textContent = error.message;
    }
  });
}

function validateLogin(values) {
  const errors = {};

  if (!values.email?.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/i.test(values.email)) {
    errors.email = "Укажите корректный email.";
  }

  if (!values.password?.trim() || values.password.trim().length < 6) {
    errors.password = "Введите пароль длиной не менее 6 символов.";
  }

  return errors;
}

function validateRegister(values) {
  const errors = {};

  if (!values.name?.trim() || values.name.trim().length < 2) {
    errors.name = "Укажите имя длиной не менее 2 символов.";
  }

  if (!values.email?.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/i.test(values.email)) {
    errors.email = "Укажите корректный email.";
  }

  if (!values.password?.trim() || values.password.trim().length < 6) {
    errors.password = "Введите пароль длиной не менее 6 символов.";
  }

  if (values.confirmPassword !== values.password) {
    errors.confirmPassword = "Пароли не совпадают.";
  }

  return errors;
}
