// Empaqueta el smoke test con esbuild para poder correrlo con node.
// Se externalizan react y jsdom para no duplicarlos en el bundle.
import { build } from 'esbuild'

await build({
  entryPoints: ['tests/smoke.mjs'],
  outfile: 'tests/.smoke.bundle.mjs',
  bundle: true,
  platform: 'node',
  format: 'esm',
  jsx: 'automatic',
  loader: { '.css': 'empty', '.mjs': 'jsx' },
  external: ['react', 'react-dom', 'react-dom/client', 'react-router-dom', 'jsdom'],
  define: {
    'import.meta.env.VITE_API_URL': JSON.stringify('http://127.0.0.1:8000/api'),
  },
})

console.log('bundle del smoke test generado')
