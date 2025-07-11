// FacultyValidation.js
export function validateFirstName(input) {
  const isValid = /^[a-zA-Z\s]{1,40}$/.test(input.value.trim());
  toggleValidationStyle(input, isValid);
  return isValid;
}

export function validateMiddleInitial(input) {
  let value = input.value.trim().toUpperCase();
  if (/^[A-Z]$/.test(value)) {
    input.value = `${value}.`;
    toggleValidationStyle(input, true);
    return true;
  }
  toggleValidationStyle(input, false);
  return false;
}

export function validateLastName(input) {
  const isValid = /^[a-zA-Z\s]{1,40}$/.test(input.value.trim());
  toggleValidationStyle(input, isValid);
  return isValid;
}

export function validateIdNumber(input) {
  const isValid = /^\d{12}$/.test(input.value.trim());
  toggleValidationStyle(input, isValid);
  return isValid;
}

export function validateEmail(input) {
  const isValid = /^[a-zA-Z0-9._%+-]+@lpunetwork\.edu\.ph$/.test(input.value.trim());
  toggleValidationStyle(input, isValid);
  return isValid;
}

export function validateRole(select) {
  const isValid = select.value.trim() !== "";
  toggleValidationStyle(select, isValid);
  return isValid;
}

function toggleValidationStyle(input, isValid) {
  input.classList.toggle("is-invalid", !isValid);
  input.classList.toggle("is-valid", isValid);
}
