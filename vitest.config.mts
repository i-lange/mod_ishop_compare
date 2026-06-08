import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    // DOM-среда нужна для smoke и будущих UI-тестов модуля сравнения.
    environment: 'happy-dom',
    include: ['tests/js/**/*.test.js'],
    coverage: {
      // Покрытие ограничено ручным JS entrypoint, generated assets исключены.
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      reportsDirectory: 'coverage/js',
      include: ['media/js/front.js'],
      exclude: [
        'media/js/*.min.js',
        'media/js/*.gz',
        'node_modules/**',
        'tests/**',
      ],
      thresholds: {
        // Строгие branch-пороги будут уместны после появления полноценного API.
        lines: 70,
        functions: 70,
        statements: 70,
        branches: 45,
      },
    },
  },
})
