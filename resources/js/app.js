import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

window.Alpine = Alpine;
window.Chart = Chart;

// Render markdown (jawaban Gemini) menjadi HTML aman — dipakai panel chat
// Strategic Advisor. Tanpa ini, penanda **tebal**/*miring* tampil mentah
// sebagai bintang-bintang di UI.
marked.setOptions({ breaks: true, gfm: true });
window.mdToHtml = (md) => DOMPurify.sanitize(marked.parse(String(md ?? '')));
// Varian inline untuk satu baris (judul tren/saran): bungkus <p> dihapus agar
// tidak menambah margin di dalam <span>.
window.mdToHtmlInline = (md) => {
    const html = DOMPurify.sanitize(marked.parseInline(String(md ?? '')));
    return html.replace(/<\/?p[^>]*>/g, '');
};

Alpine.start();
