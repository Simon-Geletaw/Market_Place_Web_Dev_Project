function isRequired(value) {
  return String(value ?? '').trim().length > 0;
}

function isRating(value) {
  const numeric = Number(value);
  return Number.isInteger(numeric) && numeric >= 1 && numeric <= 5;
}
