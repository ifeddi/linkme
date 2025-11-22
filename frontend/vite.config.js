import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    // listen on all addresses (0.0.0.0) so Docker can expose it
    host: true,
    port: 5173,
    // use polling to reliably detect file changes from a mounted host volume
    watch: {
      usePolling: true,
      // small interval (ms) for polling
      interval: 100
    },
    // configure HMR so the client connects to the host's address/port
    hmr: {
      host: 'localhost',
      port: 5173
    }
  }
})
