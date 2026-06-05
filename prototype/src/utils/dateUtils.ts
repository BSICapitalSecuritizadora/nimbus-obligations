/**
 * Formats an ISO date string (YYYY-MM-DD) to Brazilian format (DD/MM/YYYY).
 */
export function formatDateBR(isoDate: string): string {
  if (!isoDate) return '—';
  const [year, month, day] = isoDate.split('-');
  if (!year || !month || !day) return isoDate;
  return `${day}/${month}/${year}`;
}

/**
 * Returns true if the given ISO date is within the next N days from today.
 */
export function isWithinDays(isoDate: string, days: number): boolean {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const target = new Date(isoDate + 'T00:00:00');
  const diff = (target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24);
  return diff >= 0 && diff <= days;
}

/**
 * Returns true if the given ISO date is in the past.
 */
export function isPast(isoDate: string): boolean {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const target = new Date(isoDate + 'T00:00:00');
  return target < today;
}
