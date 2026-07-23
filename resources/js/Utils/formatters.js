/**
 * Format monetary amount into Malaysian Ringgit (MYR - RM).
 */
export function formatCurrency(amount, currency = 'MYR') {
  const numericAmount = parseFloat(amount) || 0;
  const formatted = new Intl.NumberFormat('en-MY', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numericAmount);

  return `RM ${formatted}`;
}

/**
 * Format ISO date string into readable Malaysian date.
 */
export function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('en-MY', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(date);
}

/**
 * Format datetime string.
 */
export function formatDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('en-MY', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}
