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
  // Theme colors
  blue: '\x1b[38;2;59;130;246m',       // Vibrant Blue (Frontend)
  red: '\x1b[38;2;239;68;68m',         // Vibrant Red (Backend)
  orange: '\x1b[38;2;249;115;22m',     // Vibrant Orange (Queue/Work)
  green: '\x1b[38;2;34;197;94m',       // Success Green
  cyan: '\x1b[38;2;6;182;212m',        // Brand Cyan
  gray: '\x1b[38;2;156;163;175m',      // Subtle Gray
  darkGray: '\x1b[38;2;107;114;128m',  // Darker Gray
};

// Prefixes: All use [nafasy] with distinct domain colors
const prefixes = {
  frontend: `${colors.blue}${colors.bold}[nafasy]${colors.reset}`,
  backend: `${colors.red}${colors.bold}[nafasy]${colors.reset}`,
  work: `${colors.orange}${colors.bold}[nafasy]${colors.reset}`,
  system: `${colors.cyan}${colors.bold}[nafasy]${colors.reset}`,
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
    } catch { }
  }
}

function shutdown() {
  if (isShuttingDown) return;
  isShuttingDown = true;

  console.log(`\n${prefixes.system} ${colors.orange}Shutting down Nafasy development server...${colors.reset}`);

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

// Filters noisy default banners from Vite and PHP built-in server
function shouldFilterNoise(rawLine, name) {
  const line = rawLine.replace(/\x1b\[[0-9;]*m/g, '').trim();
  if (!line) return true;

  if (name === 'frontend') {
    if (line.startsWith('$ vite')) return true;
    if (/^VITE\s+v[\d.]+\s+ready/i.test(line)) return true;
    if (/^➜\s+Local:/i.test(line)) return true;
    if (/^➜\s+Network:/i.test(line)) return true;
    if (/^➜\s+press\s+h/i.test(line)) return true;
    if (/Port \d+ is in use/i.test(line)) return true;
    if (/\[vite\] \(client\) Re-optimizing/i.test(line)) return true;
    if (/\[optimizer\] bundling/i.test(line)) return true;
  }

  if (name === 'backend') {
    if (/INFO\s+Server running on/i.test(line)) return true;
    if (/Press Ctrl\+C to stop the server/i.test(line)) return true;
    if (/Development Server.*started/i.test(line)) return true;
    if (/PHP \d+\.\d+\.\d+ Development Server/i.test(line)) return true;
  }

  return false;
}

// Helper to spawn a child process with line-buffered prefixed logs
function runService({ name, prefix, cmdString, cwd, filterNoise = true }) {
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
      if (filterNoise && shouldFilterNoise(line, name)) continue;
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
      if (filterNoise && shouldFilterNoise(line, name)) continue;
      if (line.trim().length > 0) {
        console.error(`${prefix} ${line}`);
      }
    }
  });

  proc.on('close', (code) => {
    if (stdoutBuffer.trim().length > 0 && (!filterNoise || !shouldFilterNoise(stdoutBuffer, name))) {
      console.log(`${prefix} ${stdoutBuffer}`);
      stdoutBuffer = '';
    }
    if (stderrBuffer.trim().length > 0 && (!filterNoise || !shouldFilterNoise(stderrBuffer, name))) {
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
${colors.cyan}${colors.bold}        .d$b.
       i$$A$$L  .d$b
     .$$F\` \`$$L.$$A$$.
    j$$'    \`4$$:\` \`$$.
   j$$'     .4$:    \`$$.
  j$$\`     .$$:      \`4$L
 :$$:____.d$$:  _____.:$$:
 \`4$$$$$$$$P\` .i$$$$$$$$P\`${colors.reset}
${colors.dim}  Rumah Nafasy Unified Full-Stack Framework${colors.reset}
`);
}

function printHelp() {
  printBanner();
  console.log(`
${colors.bold}Usage:${colors.reset}
  ${colors.green}nafasy${colors.reset} <command> [options]

${colors.bold}Available Commands:${colors.reset}
  ${colors.cyan}dev${colors.reset}         Start unified development server on ${colors.green}http://localhost:3000${colors.reset} (Default)
  ${colors.cyan}build${colors.reset}       Build Vue frontend to .nafasy/dist & run Laravel backend test suite
  ${colors.cyan}test${colors.reset}        Run Laravel backend test suite (\`php artisan test\`)
  ${colors.cyan}preview${colors.reset}     Preview the production build from .nafasy/dist on port 3000
  ${colors.cyan}clean${colors.reset}       Clean the .nafasy/dist build directory
  ${colors.cyan}backend${colors.reset}     Start Laravel server and Queue worker only
  ${colors.cyan}frontend${colors.reset}    Start Vue frontend only (port 3000)
  ${colors.cyan}work${colors.reset}        Start Queue worker only (\`php artisan queue:work\`)
  ${colors.cyan}listen${colors.reset}      Start Queue listener only (\`php artisan queue:listen\`)
  ${colors.cyan}install${colors.reset}     Install all dependencies (composer + frontend package manager)
  ${colors.cyan}artisan${colors.reset}     Run an allowed Laravel maintenance command

${colors.bold}Laravel Maintenance Commands:${colors.reset}
  ${colors.cyan}cache:clear${colors.reset}     Clear application cache (alias: \`chace:clear\`)
  ${colors.cyan}config:clear${colors.reset}    Clear cached configuration
  ${colors.cyan}config:cache${colors.reset}    Build cached configuration
  ${colors.cyan}event:clear${colors.reset}     Clear cached events
  ${colors.cyan}event:cache${colors.reset}     Build cached events
  ${colors.cyan}optimize:clear${colors.reset} Clear Laravel caches (alias: \`clear\`)
  ${colors.cyan}optimize${colors.reset}         Cache Laravel configuration, events, routes, and views
  ${colors.cyan}queue:clear${colors.reset}     Clear queued jobs for the default connection
  ${colors.cyan}queue:restart${colors.reset}   Restart queue workers after the current job
  ${colors.cyan}route:clear${colors.reset}     Clear cached routes
  ${colors.cyan}route:cache${colors.reset}     Build cached routes
  ${colors.cyan}route:list${colors.reset}      List registered routes
  ${colors.cyan}storage:link${colors.reset}   Create the public storage symlink
  ${colors.cyan}view:clear${colors.reset}     Clear compiled Blade views
  ${colors.cyan}view:cache${colors.reset}     Build compiled Blade views

  Semua command di atas juga bisa dipanggil sebagai \`nafasy artisan <command>\`.
  ${colors.cyan}help${colors.reset}        Show this help message

${colors.bold}Log Legend:${colors.reset}
  ${prefixes.frontend} ${colors.blue}Blue${colors.reset}    Frontend (Vue)
  ${prefixes.backend} ${colors.red}Red${colors.reset}     Backend (Laravel API)
  ${prefixes.work} ${colors.orange}Orange${colors.reset}  Queue Worker (Jobs)
`);
}

async function startDev({ queueMode = 'listen' } = {}) {
  printBanner();

  if (!fs.existsSync(BACKEND_DIR) || !fs.existsSync(FRONTEND_DIR)) {
    console.error(`${prefixes.system} ${colors.red}Error: backend or frontend directory not found in ${ROOT_DIR}${colors.reset}`);
    process.exit(1);
  }

  const pkgManager = getFrontendPkgManager();

  // Unified Framework Ready Message (Only showing localhost:3000)
  console.log(`  ${colors.green}${colors.bold}➜${colors.reset}  ${colors.bold}Local:${colors.reset}    ${colors.cyan}${colors.bold}http://localhost:3000/${colors.reset}`);
  console.log(`  ${colors.gray}➜  Backend:  http://127.0.0.1:8000 (proxied via /api)${colors.reset}`);
  console.log(`  ${colors.gray}➜  Queue:    Active (database driver)${colors.reset}`);
  console.log(`\n  ${colors.darkGray}Ready for requests. Logs will appear below:${colors.reset}`);
  console.log(`  ${colors.darkGray}Legend: ${colors.blue}■ Frontend${colors.reset}  ${colors.red}■ Backend${colors.reset}  ${colors.orange}■ Queue${colors.reset}\n`);

  // 1. Start Laravel Server (backend)
  runService({
    name: 'backend',
    prefix: prefixes.backend,
    cmdString: 'php artisan serve',
    cwd: BACKEND_DIR,
    filterNoise: true,
  });

  // 2. Start Laravel Queue Worker (backend)
  // Using queue:listen with verbose mode for automatic job reload and live logging
  const queueCmd = queueMode === 'work'
    ? 'php artisan queue:work --tries=3 --verbose'
    : 'php artisan queue:listen --tries=3 --verbose';

  runService({
    name: 'work',
    prefix: prefixes.work,
    cmdString: queueCmd,
    cwd: BACKEND_DIR,
    filterNoise: false,
  });

  // 3. Start Vue Frontend (frontend on port 3000)
  runService({
    name: 'frontend',
    prefix: prefixes.frontend,
    cmdString: `${pkgManager} run dev --port 3000`,
    cwd: FRONTEND_DIR,
    filterNoise: true,
  });

  // Initial queue ready confirmation log
  setTimeout(() => {
    console.log(`${prefixes.work} ${colors.orange}Queue worker active & listening for jobs...${colors.reset}`);
  }, 1000);
}

async function runBuild({ skipTest = false } = {}) {
  printBanner();
  const pkgManager = getFrontendPkgManager();

  console.log(`${prefixes.system} ${colors.bold}Starting Nafasy full-stack build pipeline...${colors.reset}`);
  console.log(`${prefixes.system} ${colors.gray}Output directory:${colors.reset} ${colors.cyan}.nafasy/dist${colors.reset}\n`);

  // Step 1: Build Vue Frontend
  console.log(`${prefixes.frontend} ${colors.bold}${colors.blue}Building Vue Frontend (Vite)...${colors.reset}`);
  const buildSuccess = await executeCommandStreaming({
    prefix: prefixes.frontend,
    cmdString: `${pkgManager} run build`,
    cwd: FRONTEND_DIR,
  });

  if (!buildSuccess) {
    console.error(`\n${prefixes.system} ${colors.red}${colors.bold}Frontend build failed!${colors.reset}\n`);
    process.exit(1);
  }

  console.log(`${prefixes.system} ${colors.green}Frontend build completed successfully -> .nafasy/dist/${colors.reset}\n`);

  // Step 2: Run Backend Tests
  if (!skipTest) {
    console.log(`${prefixes.backend} ${colors.bold}${colors.red}Running Backend Test Suite (Laravel Artisan Test)...${colors.reset}`);
    const testSuccess = await executeCommandStreaming({
      prefix: prefixes.backend,
      cmdString: 'php artisan test',
      cwd: BACKEND_DIR,
    });

    if (!testSuccess) {
      console.log(`\n${prefixes.system} ${colors.orange}Notice: Some backend tests did not pass. Please check the logs above.${colors.reset}`);
    } else {
      console.log(`${prefixes.system} ${colors.green}All backend tests passed successfully!${colors.reset}`);
    }
  }

  console.log(`\n${prefixes.system} ${colors.bold}${colors.green}✔ Nafasy build pipeline finished!${colors.reset}`);
  console.log(`${prefixes.system} Artifacts stored in: ${colors.cyan}${path.relative(process.cwd(), NAFASY_DIST_DIR)}${colors.reset}\n`);
}

async function runTest() {
  printBanner();
  console.log(`${prefixes.backend} ${colors.bold}Running Laravel Backend Tests...${colors.reset}\n`);

  await executeCommandStreaming({
    prefix: prefixes.backend,
    cmdString: 'php artisan test',
    cwd: BACKEND_DIR,
  });
}

async function runPreview() {
  printBanner();
  const pkgManager = getFrontendPkgManager();
  console.log(`${prefixes.system} ${colors.bold}Preparing production-like preview...${colors.reset}`);

  const optimizeSuccess = await executeCommandStreaming({
    prefix: prefixes.backend,
    cmdString: 'php artisan optimize',
    cwd: BACKEND_DIR,
  });

  if (!optimizeSuccess) {
    console.error(`${prefixes.system} ${colors.red}Laravel optimization failed; preview was not started.${colors.reset}\n`);
    process.exitCode = 1;
    return;
  }

  console.log(`\n  ${colors.green}${colors.bold}➜${colors.reset}  ${colors.bold}Frontend:${colors.reset} ${colors.cyan}${colors.bold}http://localhost:3000/${colors.reset}`);
  console.log(`  ${colors.red}${colors.bold}➜${colors.reset}  ${colors.bold}Backend:${colors.reset}  ${colors.cyan}${colors.bold}http://127.0.0.1:8000/${colors.reset}`);
  console.log(`  ${colors.gray}Laravel caches are enabled; press Ctrl+C to stop both services.${colors.reset}\n`);

  runService({
    name: 'backend',
    prefix: prefixes.backend,
    cmdString: 'php artisan serve --host=127.0.0.1 --port=8000',
    cwd: BACKEND_DIR,
    filterNoise: true,
  });

  runService({
    name: 'frontend',
    prefix: prefixes.frontend,
    cmdString: `${pkgManager} run preview --port 3000 --outDir ../.nafasy/dist`,
    cwd: FRONTEND_DIR,
    filterNoise: true,
  });
}

function runClean() {
  printBanner();
  console.log(`${prefixes.system} ${colors.bold}Cleaning .nafasy/dist directory...${colors.reset}`);
  try {
    if (fs.existsSync(NAFASY_DIST_DIR)) {
      fs.rmSync(NAFASY_DIST_DIR, { recursive: true, force: true });
      console.log(`${prefixes.system} ${colors.green}Successfully removed .nafasy/dist${colors.reset}\n`);
    } else {
      console.log(`${prefixes.system} ${colors.gray}.nafasy/dist is already clean.${colors.reset}\n`);
    }
  } catch (err) {
    console.error(`${prefixes.system} ${colors.red}Failed to clean .nafasy/dist: ${err.message}${colors.reset}\n`);
  }
}

function startBackend() {
  printBanner();
  console.log(`${prefixes.backend} ${colors.bold}Starting Laravel backend & Queue worker...${colors.reset}\n`);

  runService({
    name: 'backend',
    prefix: prefixes.backend,
    cmdString: 'php artisan serve',
    cwd: BACKEND_DIR,
    filterNoise: false,
  });

  runService({
    name: 'work',
    prefix: prefixes.work,
    cmdString: 'php artisan queue:listen --tries=3 --verbose',
    cwd: BACKEND_DIR,
    filterNoise: false,
  });
}

function startFrontend() {
  printBanner();
  const pkgManager = getFrontendPkgManager();
  console.log(`${prefixes.frontend} ${colors.bold}Starting Vue frontend on http://localhost:3000...${colors.reset}\n`);

  runService({
    name: 'frontend',
    prefix: prefixes.frontend,
    cmdString: `${pkgManager} run dev --port 3000`,
    cwd: FRONTEND_DIR,
    filterNoise: false,
  });
}

function startQueue(mode = 'listen') {
  printBanner();
  const cmd = mode === 'work'
    ? 'php artisan queue:work --tries=3 --verbose'
    : 'php artisan queue:listen --tries=3 --verbose';
  console.log(`${prefixes.work} ${colors.bold}Starting Queue (${cmd})...${colors.reset}\n`);

  runService({
    name: 'work',
    prefix: prefixes.work,
    cmdString: cmd,
    cwd: BACKEND_DIR,
    filterNoise: false,
  });
}

function runInstall() {
  printBanner();
  const pkgManager = getFrontendPkgManager();
  console.log(`${prefixes.system} ${colors.bold}Installing dependencies for Backend and Frontend...${colors.reset}\n`);

  console.log(`${prefixes.backend} ${colors.red}1/2 Installing backend composer dependencies...${colors.reset}`);
  try {
    execSync('composer install', { cwd: BACKEND_DIR, stdio: 'inherit' });
  } catch (err) {
    console.error(`${prefixes.backend} ${colors.red}Failed to install composer dependencies.${colors.reset}`);
  }

  console.log(`\n${prefixes.frontend} ${colors.blue}2/2 Installing frontend packages (${pkgManager})...${colors.reset}`);
  try {
    execSync(`${pkgManager} install`, { cwd: FRONTEND_DIR, stdio: 'inherit' });
  } catch (err) {
    console.error(`${prefixes.frontend} ${colors.red}Failed to install frontend dependencies.${colors.reset}`);
  }

  console.log(`\n${prefixes.system} ${colors.green}${colors.bold}All dependencies installed successfully!${colors.reset}\n`);
}

const artisanCommands = new Map([
  ['about', 'php artisan about'],
  ['cache:clear', 'php artisan cache:clear'],
  ['config:clear', 'php artisan config:clear'],
  ['config:cache', 'php artisan config:cache'],
  ['event:clear', 'php artisan event:clear'],
  ['event:cache', 'php artisan event:cache'],
  ['optimize:clear', 'php artisan optimize:clear'],
  ['optimize', 'php artisan optimize'],
  ['queue:clear', 'php artisan queue:clear'],
  ['queue:restart', 'php artisan queue:restart'],
  ['route:clear', 'php artisan route:clear'],
  ['route:cache', 'php artisan route:cache'],
  ['route:list', 'php artisan route:list'],
  ['storage:link', 'php artisan storage:link'],
  ['view:clear', 'php artisan view:clear'],
  ['view:cache', 'php artisan view:cache'],
]);

const artisanAliases = new Map([
  ['chace:clear', 'cache:clear'],
  ['clear-cache', 'optimize:clear'],
  ['clear', 'optimize:clear'],
]);

async function runArtisan(command) {
  const resolvedCommand = artisanAliases.get(command) || command;
  const artisanCommand = artisanCommands.get(resolvedCommand);

  printBanner();

  if (!artisanCommand) {
    console.error(`${prefixes.system} ${colors.red}Artisan command tidak diizinkan: ${command}${colors.reset}`);
    console.log(`${prefixes.system} ${colors.gray}Gunakan "nafasy help" untuk daftar command operasional yang tersedia.\n`);
    process.exitCode = 1;
    return;
  }

  console.log(`${prefixes.backend} ${colors.bold}Running ${artisanCommand}...${colors.reset}\n`);
  const success = await executeCommandStreaming({
    prefix: prefixes.backend,
    cmdString: artisanCommand,
    cwd: BACKEND_DIR,
  });

  if (!success) process.exitCode = 1;
}

// Main CLI router
const args = process.argv.slice(2);
const command = args[0] || 'dev';

switch (command) {
  case 'dev':
    const queueMode = args.includes('--work') ? 'work' : 'listen';
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
  case 'artisan':
    runArtisan(args[1]);
    break;
  case 'help':
  case '--help':
  case '-h':
    printHelp();
    break;
  default:
    if (artisanCommands.has(command) || artisanAliases.has(command)) {
      runArtisan(command);
    } else {
      console.error(`${prefixes.system} ${colors.red}Unknown command: ${command}${colors.reset}`);
      printHelp();
      process.exit(1);
    }
}
