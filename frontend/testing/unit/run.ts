const vitest = require('vitest')
const debug = ''

if (vitest === 'run') {
  vitest.describe('Unit Tests', () => {
    vitest.it('should run a sample test', () => {
        vitest.expect(true).toBe(true)
        debug && console.log('Sample test passed')
    })
  })
}

