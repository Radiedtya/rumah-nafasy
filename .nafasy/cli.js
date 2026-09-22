#!/usr/bin/env node

/**
 * Nafasy Framework Dev Runner & Build Orchestrator CLI
 * Clean, lightweight orchestrator for Laravel & Vue
 */

import { spawn, execSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import fs from 'node:fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Find project root by locating backend and frontend directories
function findProjectRoot(startDir = process.cwd()) {
  let curr = startDir;
  while (true) {
    if (fs.existsSync(path.join(curr, 'backend')) && fs.existsSync(path.join(curr, 'frontend'))) {
      return curr;
    }
    const parent = path.dirname(curr);
    if (parent === curr) break;
    curr = parent;
  }
  // Fallback to parent of .nafasy folder
  const fallback = path.resolve(__dirname, '..');
  if (fs.existsSync(path.join(fallback, 'backend')) && fs.existsSync(path.join(fallback, 'frontend'))) {
    return fallback;
  }
  return process.cwd();
}

const ROOT_DIR = findProjectRoot();
const BACKEND_DIR = path.join(ROOT_DIR, 'backend');
const FRONTEND_DIR = path.join(ROOT_DIR, 'frontend');
const NAFASY_DIST_DIR = path.join(ROOT_DIR, '.nafasy', 'dist');

// ANSI Color Palette
const colors = {
  reset: '\x1b[0m',
  bold: '\x1b[1m',
  dim: '\x1b[2m',
  green: '\x1b[38;2;34;197;94m',
  cyan: '\x1b[38;2;6;182;212m',
  yellow: '\x1b[38;2;245;158;11m',
  red: '\x1b[38;2;239;68;68m',
  gray: '\x1b[38;2;156;163;175m',
};

const prefixes = {
  vue: `${colors.cyan}${colors.bold}[vue]    ${colors.reset}`,
  laravel: `${colors.red}${colors.bold}[laravel]${colors.reset}`,
  work: `${colors.yellow}${colors.bold}[work]   ${colors.reset}`,
  nafasy: `${colors.green}${colors.bold}[nafasy] ${colors.reset}`,
};

const activeProcesses = [];
let isShuttingDown = false;

// Graceful process killer across Windows & POSIX
function killProcess(proc) {
  if (!proc || !proc.pid) return;
  try {
    if (process.platform === 'win32') {
      execSync(`taskkill /pid ${proc.pid} /T /F`, { stdio: 'ignore' });
    } else {
      process.kill(-proc.pid, 'SIGTERM');
    }
  } catch {
    try {
      proc.kill('SIGTERM');
    } catch {}
  }
}

function shutdown() {
  if (isShuttingDown) return;
  isShuttingDown = true;

  console.log(`\n${prefixes.nafasy} ${colors.yellow}Shutting down all services...${colors.reset}`);
  
  for (const item of activeProcesses) {
    killProcess(item.process);
  }

  setTimeout(() => {
    process.exit(0);
  }, 200);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
process.on('SIGHUP', shutdown);
process.on('exit', () => {
  for (const item of activeProcesses) {
    killProcess(item.process);
  }
});

// Helper to spawn a child process with line-buffered prefixed logs
function runService({ name, prefix, cmdString, cwd }) {
  const isWin = process.platform === 'win32';
  
  const proc = spawn(cmdString, {
    cwd,
    shell: true,
    stdio: ['ignore', 'pipe', 'pipe'],
    detached: !isWin,
    env: {
      ...process.env,
      FORCE_COLOR: '1',
      NODE_ENV: process.env.NODE_ENV || 'development',
    },
  });

  activeProcesses.push({ name, process: proc });

  let stdoutBuffer = '';
  proc.stdout.on('data', (data) => {
    stdoutBuffer += data.toString();
    const lines = stdoutBuffer.split(/\r?\n/);
    stdoutBuffer = lines.pop() || '';
    for (const line of lines) {
      if (line.trim().length > 0) {
        console.log(`${prefix} ${line}`);
      }
    }
  });

  let stderrBuffer = '';
  proc.stderr.on('data', (data) => {
    stderrBuffer += data.toString();
    const lines = stderrBuffer.split(/\r?\n/);
    stderrBuffer = lines.pop() || '';
    for (const line of lines) {
      if (line.trim().length > 0) {
        console.error(`${prefix} ${colors.red}${line}${colors.reset}`);
      }
    }
  });

  proc.on('close', (code) => {
    if (stdoutBuffer.trim().length > 0) {
      console.log(`${prefix} ${stdoutBuffer}`);
      stdoutBuffer = '';
    }
    if (stderrBuffer.trim().length > 0) {
      console.error(`${prefix} ${stderrBuffer}`);
      stderrBuffer = '';
    }
    if (!isShuttingDown && code !== 0 && code !== null) {
      console.log(`${prefix} ${colors.red}Process exited with code ${code}${colors.reset}`);
    }
  });

  proc.on('error', (err) => {
    console.error(`${prefix} ${colors.red}Failed to start: ${err.message}${colors.reset}`);
  });

  return proc;
}

// Synchronous prefixed command execution with live streaming output
function executeCommandStreaming({ prefix, cmdString, cwd }) {
  return new Promise((resolve) => {
    const proc = spawn(cmdString, {
      cwd,
      shell: true,
      stdio: ['ignore', 'pipe', 'pipe'],
      env: {
        ...process.env,
        FORCE_COLOR: '1',
      },
    });

    let stdoutBuffer = '';
    proc.stdout.on('data', (data) => {
      stdoutBuffer += data.toString();
      const lines = stdoutBuffer.split(/\r?\n/);
      stdoutBuffer = lines.pop() || '';
      for (const line of lines) {
        if (line.trim().length > 0) {
          console.log(`${prefix} ${line}`);
        }
      }
    });

    let stderrBuffer = '';
    proc.stderr.on('data', (data) => {
      stderrBuffer += data.toString();
      const lines = stderrBuffer.split(/\r?\n/);
      stderrBuffer = lines.pop() || '';
      for (const line of lines) {
        if (line.trim().length > 0) {
          console.error(`${prefix} ${line}`);
        }
      }
    });

    proc.on('close', (code) => {
      if (stdoutBuffer.trim().length > 0) {
        console.log(`${prefix} ${stdoutBuffer}`);
      }
      if (stderrBuffer.trim().length > 0) {
        console.error(`${prefix} ${stderrBuffer}`);
      }
      resolve(code === 0);
    });

    proc.on('error', (err) => {
      console.error(`${prefix} ${colors.red}Execution error: ${err.message}${colors.reset}`);
      resolve(false);
    });
  });
}

// Detect package manager for frontend (pnpm > npm)
function getFrontendPkgManager() {
  if (fs.existsSync(path.join(FRONTEND_DIR, 'pnpm-lock.yaml'))) {
    return 'pnpm';
  }
  return 'npm';
}

function printBanner() {
  console.log(`
${colors.cyan}${colors.bold}  _   _          __                  
 | \\ | |        / _|                 
 |  \\| |  __ _ | |_  __ _  ___  _   _ 
 | . \` | / _\` ||  _|/ _\` |/ __|| | | |
 | |\\  || (_| || | | (_| |\\__ \\| |_| |
 |_| \\_| \\__,_||_|  \\__,_||___/ \\__, |
                                 __/ |
                                |___/ ${colors.reset}
${colors.dim}  Rumah Nafasy Unified Development & Build Framework${colors.reset}
`);
}

function printHelp() {
  printBanner();
  console.log(`
${colors.bold}Usage:${colors.reset}
  ${colors.green}nafasy${colors.reset} <command> [options]

${colors.bold}Available Commands:${colors.reset}
  ${colors.cyan}dev${colors.reset}         Start Vue frontend, Laravel backend, and Queue worker together (Default)
  ${colors.cyan}build${colors.reset}       Build Vue frontend to .nafasy/dist & run Laravel backend test suite
  ${colors.cyan}test${colors.reset}        Run Laravel backend test suite (\`php artisan test\`)
  ${colors.cyan}preview${colors.reset}     Preview the production build stored in .nafasy/dist
  ${colors.cyan}backend${colors.reset}     Start Laravel server and Queue worker only
  ${colors.cyan}frontend${colors.reset}    Start Vue frontend only
  ${colors.cyan}work${colors.reset}        Start Queue worker only (\`php artisan queue:work\`)
  ${colors.cyan}listen${colors.reset}      Start Queue listener only (\`php artisan queue:listen\`)
  ${colors.cyan}clean${colors.reset}       Clean the .nafasy/dist build directory
  ${colors.cyan}install${colors.reset}     Install all dependencies (composer + frontend package manager)
  ${colors.cyan}help${colors.reset}        Show this help message

${colors.bold}Examples:${colors.reset}
  ${colors.gray}$${colors.reset} nafasy dev
  ${colors.gray}$${colors.reset} nafasy build
  ${colors.gray}$${colors.reset} nafasy test
  ${colors.gray}$${colors.reset} nafasy preview
`);
}

async function startDev({ queueMode = 'work' } = {}) {
  printBanner();
  
  if (!fs.existsSync(BACKEND_DIR) || !fs.existsSync(FRONTEND_DIR)) {
    console.error(`${prefixes.nafasy} ${colors.red}Error: backend or frontend directory not found in ${ROOT_DIR}${colors.reset}`);
    process.exit(1);
  }

  const pkgManager = getFrontendPkgManager();

  console.log(`${prefixes.nafasy} ${colors.bold}Starting all development services...${colors.reset}`);
  console.log(`${prefixes.nafasy} ${colors.gray}Press Ctrl+C to stop all services.${colors.reset}\n`);

  // 1. Start Laravel Server (backend)
  runService({
    name: 'laravel',
    prefix: prefixes.laravel,
    cmdString: 'php artisan serve',
    cwd: BACKEND_DIR,
  });

  // 2. Start Laravel Queue Worker (backend)
  const queueCmd = queueMode === 'listen' ? 'php artisan queue:listen' : 'php artisan queue:work';
  runService({
    name: 'work',
    prefix: prefixes.work,
    cmdString: queueCmd,
    cwd: BACKEND_DIR,
  });

  // 3. Start Vue Frontend (frontend)
  runService({
    name: 'vue',
    prefix: prefixes.vue,
    cmdString: `${pkgManager} run dev`,
    cwd: FRONTEND_DIR,
  });
}

async function runBuild({ skipTest = false } = {}) {
  printBanner();
  const pkgManager = getFrontendPkgManager();

  console.log(`${prefixes.nafasy} ${colors.bold}Starting Nafasy full-stack build pipeline...${colors.reset}`);
  console.log(`${prefixes.nafasy} ${colors.gray}Output directory:${colors.reset} ${colors.cyan}.nafasy/dist${colors.reset}\n`);

  // Step 1: Build Vue Frontend
  console.log(`${prefixes.nafasy} ${colors.bold}${colors.cyan}Step 1/2: Building Vue Frontend (Vite)...${colors.reset}`);
  const buildSuccess = await executeCommandStreaming({
    prefix: prefixes.vue,
    cmdString: `${pkgManager} run build`,
    cwd: FRONTEND_DIR,
  });

  if (!buildSuccess) {
    console.error(`\n${prefixes.nafasy} ${colors.red}${colors.bold}Frontend build failed!${colors.reset}\n`);
    process.exit(1);
  }

  console.log(`${prefixes.nafasy} ${colors.green}Frontend build completed successfully -> .nafasy/dist/${colors.reset}\n`);

  // Step 2: Run Backend Tests
  if (!skipTest) {
    console.log(`${prefixes.nafasy} ${colors.bold}${colors.red}Step 2/2: Running Backend Test Suite (Laravel Artisan Test)...${colors.reset}`);
    const testSuccess = await executeCommandStreaming({
      prefix: prefixes.laravel,
      cmdString: 'php artisan test',
      cwd: BACKEND_DIR,
    });

    if (!testSuccess) {
      console.log(`\n${prefixes.nafasy} ${colors.yellow}Notice: Some backend tests did not pass. Please check the logs above.${colors.reset}`);
    } else {
      console.log(`${prefixes.nafasy} ${colors.green}All backend tests passed successfully!${colors.reset}`);
    }
  }

  console.log(`\n${prefixes.nafasy} ${colors.bold}${colors.green}✔ Nafasy build pipeline finished!${colors.reset}`);
  console.log(`${prefixes.nafasy} Artifacts stored in: ${colors.cyan}${path.relative(process.cwd(), NAFASY_DIST_DIR)}${colors.reset}\n`);
}

async function runTest() {
  printBanner();
  console.log(`${prefixes.nafasy} ${colors.bold}Running Laravel Backend Tests...${colors.reset}\n`);

  await executeCommandStreaming({
    prefix: prefixes.laravel,
    cmdString: 'php artisan test',
    cwd: BACKEND_DIR,
  });
}

function runPreview() {
  printBanner();
  const pkgManager = getFrontendPkgManager();
  console.log(`${prefixes.nafasy} ${colors.bold}Previewing production build from .nafasy/dist...${colors.reset}\n`);

  runService({
    name: 'vue',
    prefix: prefixes.vue,
    cmdString: `${pkgManager} run preview --outDir ../.nafasy/dist`,
    cwd: FRONTEND_DIR,
  });
}

function runClean() {
  printBanner();
  console.log(`${prefixes.nafasy} ${colors.bold}Cleaning .nafasy/dist directory...${colors.reset}`);
  try {
    if (fs.existsSync(NAFASY_DIST_DIR)) {
      fs.rmSync(NAFASY_DIST_DIR, { recursive: true, force: true });
      console.log(`${prefixes.nafasy} ${colors.green}Successfully removed .nafasy/dist${colors.reset}\n`);
    } else {
      console.log(`${prefixes.nafasy} ${colors.gray}.nafasy/dist is already clean.${colors.reset}\n`);
    }
  } catch (err) {
    console.error(`${prefixes.nafasy} ${colors.red}Failed to clean .nafasy/dist: ${err.message}${colors.reset}\n`);
  }
}

function startBackend() {
  printBanner();
  console.log(`${prefixes.nafasy} ${colors.bold}Starting Laravel backend & Queue worker...${colors.reset}\n`);

  runService({
    name: 'laravel',
    prefix: prefixes.laravel,
    cmdString: 'php artisan serve',
    cwd: BACKEND_DIR,
  });

  runService({
    name: 'work',
    prefix: prefixes.work,
    cmdString: 'php artisan queue:work',
    cwd: BACKEND_DIR,
  });
}

function startFrontend() {
  printBanner();
  const pkgManager = getFrontendPkgManager();
  console.log(`${prefixes.nafasy} ${colors.bold}Starting Vue frontend...${colors.reset}\n`);

  runService({
    name: 'vue',
    prefix: prefixes.vue,
    cmdString: `${pkgManager} run dev`,
    cwd: FRONTEND_DIR,
  });
}

function startQueue(mode = 'work') {
  printBanner();
  const cmd = mode === 'listen' ? 'php artisan queue:listen' : 'php artisan queue:work';
  console.log(`${prefixes.nafasy} ${colors.bold}Starting Queue (${cmd})...${colors.reset}\n`);

  runService({
    name: 'work',
    prefix: prefixes.work,
    cmdString: cmd,
    cwd: BACKEND_DIR,
  });
}

function runInstall() {
  printBanner();
  const pkgManager = getFrontendPkgManager();
  console.log(`${prefixes.nafasy} ${colors.bold}Installing dependencies for Backend and Frontend...${colors.reset}\n`);

  console.log(`${prefixes.nafasy} ${colors.cyan}1/2 Installing backend composer dependencies...${colors.reset}`);
  try {
    execSync('composer install', { cwd: BACKEND_DIR, stdio: 'inherit' });
  } catch (err) {
    console.error(`${prefixes.nafasy} ${colors.red}Failed to install composer dependencies.${colors.reset}`);
  }

  console.log(`\n${prefixes.nafasy} ${colors.cyan}2/2 Installing frontend packages (${pkgManager})...${colors.reset}`);
  try {
    execSync(`${pkgManager} install`, { cwd: FRONTEND_DIR, stdio: 'inherit' });
  } catch (err) {
    console.error(`${prefixes.nafasy} ${colors.red}Failed to install frontend dependencies.${colors.reset}`);
  }

  console.log(`\n${prefixes.nafasy} ${colors.green}${colors.bold}All dependencies installed successfully!${colors.reset}\n`);
}

// Main CLI router
const args = process.argv.slice(2);
const command = args[0] || 'dev';

switch (command) {
  case 'dev':
    const queueMode = args.includes('--listen') ? 'listen' : 'work';
    startDev({ queueMode });
    break;
  case 'build':
    const skipTest = args.includes('--no-test');
    runBuild({ skipTest });
    break;
  case 'test':
    runTest();
    break;
  case 'preview':
    runPreview();
    break;
  case 'clean':
    runClean();
    break;
  case 'backend':
    startBackend();
    break;
  case 'frontend':
  case 'vue':
    startFrontend();
    break;
  case 'work':
  case 'queue':
    startQueue('work');
    break;
  case 'listen':
    startQueue('listen');
    break;
  case 'install':
  case 'i':
    runInstall();
    break;
  case 'help':
  case '--help':
  case '-h':
    printHelp();
    break;
  default:
    console.error(`${prefixes.nafasy} ${colors.red}Unknown command: ${command}${colors.reset}`);
    printHelp();
    process.exit(1);
}
