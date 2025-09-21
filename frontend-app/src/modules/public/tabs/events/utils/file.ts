export function formatBytes(bytes?: number) {
  if (!bytes || bytes <= 0) return ''
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  const val = bytes / Math.pow(1024, i)
  return `${val.toFixed(val >= 10 || i === 0 ? 0 : 1)} ${sizes[i]}`
}

export function iconForMime(mime: string) {
  if (mime.includes('pdf')) return 'mdi:file-pdf-box'
  if (mime.includes('zip') || mime.includes('compressed')) return 'mdi:folder-zip'
  if (mime.includes('csv')) return 'mdi:file-delimited'
  if (mime.includes('excel') || mime.includes('spreadsheet')) return 'mdi:file-excel'
  if (mime.includes('image')) return 'mdi:file-image'
  if (mime.includes('word')) return 'mdi:file-word'
  if (mime.includes('powerpoint') || mime.includes('presentation')) return 'mdi:file-powerpoint'
  if (mime.includes('text')) return 'mdi:file-document-outline'
  return 'mdi:file'
}
