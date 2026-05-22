<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { dashboard, login, register } from "@/routes";

withDefaults(
  defineProps<{
    canRegister: boolean;
  }>(),
  {
    canRegister: true,
  }
);
</script>

<template>
  <Head title="HRSSC">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

  <div
    class="relative min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-[#0a0a0a] dark:via-[#0f0f0f] dark:to-[#111] overflow-hidden"
  >
    <!-- Background Glow Effects -->
    <div
      class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-blue-500/20 blur-3xl rounded-full"
    ></div>
    <div
      class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-indigo-500/20 blur-3xl rounded-full"
    ></div>

    <!-- Grid Overlay -->
    <div
      class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#1f1f1f_1px,transparent_1px)] [background-size:20px_20px] opacity-30"
    ></div>

    <!-- Top Navigation -->
    <header
      class="relative z-10 w-full px-6 py-4 flex items-center justify-between max-w-6xl mx-auto backdrop-blur-md bg-white/60 dark:bg-black/30 rounded-b-2xl border-b border-slate-200 dark:border-[#222]"
    >
      <div class="flex items-center gap-3">
        <img
          src="/storage/images/SPWR_BIG.svg"
          class="h-10 dark:hidden transition-all"
          alt="logo"
        />
        <img
          src="/storage/images/SPWR_BIG.D.svg"
          class="h-10 hidden dark:block transition-all"
          alt="logo"
        />
        <div class="font-semibold text-lg text-slate-800 dark:text-white tracking-wide">
          <!--DECS 2.0-->
        </div>
      </div>

      <nav class="flex items-center gap-3">
        <Link
          v-if="$page.props.auth.user"
          :href="dashboard()"
          class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm shadow-md hover:scale-105 transition"
        >
          Dashboard
        </Link>

        <template v-else>
          <Link
            :href="login()"
            class="px-4 py-2 rounded-xl text-sm text-slate-700 hover:bg-slate-200/70 dark:text-slate-200 dark:hover:bg-white/10 transition"
          >
            Login
          </Link>
        </template>
      </nav>
    </header>

    <!-- Hero Section -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-6">
      <div class="max-w-6xl w-full grid md:grid-cols-2 gap-12 items-center">
        <!-- Left -->
        <div>
          <div
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-600/10 text-blue-600 text-xs font-medium mb-4"
          >
            Secure • Cloud-Based • Automated
          </div>

          <h1
            class="text-5xl md:text-6xl font-bold text-slate-900 dark:text-white leading-tight"
          >
            ACCESS HRSSC
            <span
              class="bg-gradient-to-r from-blue-600 to-indigo-500 bg-clip-text text-transparent"
            >
              Management System
            </span>
          </h1>

          <p class="mt-5 text-slate-600 dark:text-slate-300 text-lg leading-relaxed">
            Attendance, Certification, Clock-in, Employees and Service System
          </p>
          <p class="mt-5 text-slate-600 dark:text-slate-300 text-lg leading-relaxed">
            Streamline payroll processing, automate deductions, and manage employee
            records with precision and speed.
          </p>

          <div class="mt-8 flex gap-4">
            <Link
              :href="login()"
              class="px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium shadow-lg hover:scale-105 transition"
            >
              Get Started
            </Link>

            <a
              href="#features"
              class="px-6 py-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-white/60 dark:hover:bg-white/10 transition"
            >
              Explore Features
            </a>
          </div>
        </div>

        <!-- Right Card -->
        <div
          class="relative bg-white/70 dark:bg-[#161615]/70 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-slate-200 dark:border-[#2a2a2a] hover:scale-[1.02] transition"
        >
          <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-6">
            System Overview
          </h2>

          <ul class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
            <li>✔ Automated payroll computation</li>
            <li>✔ Timekeeping & attendance tracking</li>
            <li>✔ Government deductions (SSS, PhilHealth, Pag-IBIG)</li>
            <li>✔ Leave & overtime management</li>
            <li>✔ Exportable payroll reports</li>
          </ul>

          <div
            class="mt-8 p-4 rounded-2xl bg-gradient-to-r from-slate-50 to-slate-100 dark:from-[#1c1c1c] dark:to-[#151515] text-xs text-slate-500 border border-slate-200 dark:border-[#2a2a2a]"
          >
            ✔ Secure • ✔ Accurate • ✔ Compliant
          </div>
        </div>
      </div>
    </main>

    <!-- Features -->
    <section id="features" class="relative z-10 py-20 px-6">
      <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-8">
        <div
          class="p-6 rounded-2xl bg-white/60 dark:bg-[#161615]/60 backdrop-blur-lg border border-slate-200 dark:border-[#2a2a2a] hover:-translate-y-1 transition shadow-lg"
        >
          <h3 class="font-semibold text-slate-800 dark:text-white">
            ⚙ Payroll Automation
          </h3>
          <p class="text-sm text-slate-600 dark:text-slate-300 mt-2">
            Reduce manual computation errors with automated payroll workflows.
          </p>
        </div>

        <div
          class="p-6 rounded-2xl bg-white/60 dark:bg-[#161615]/60 backdrop-blur-lg border border-slate-200 dark:border-[#2a2a2a] hover:-translate-y-1 transition shadow-lg"
        >
          <h3 class="font-semibold text-slate-800 dark:text-white">
            🧾 Compliance Ready
          </h3>
          <p class="text-sm text-slate-600 dark:text-slate-300 mt-2">
            Fully aligned with government contributions and tax regulations.
          </p>
        </div>

        <div
          class="p-6 rounded-2xl bg-white/60 dark:bg-[#161615]/60 backdrop-blur-lg border border-slate-200 dark:border-[#2a2a2a] hover:-translate-y-1 transition shadow-lg"
        >
          <h3 class="font-semibold text-slate-800 dark:text-white">
            📊 Analytics & Reports
          </h3>
          <p class="text-sm text-slate-600 dark:text-slate-300 mt-2">
            Generate real-time payroll insights and downloadable reports.
          </p>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer
      class="relative z-10 py-8 text-center text-xs text-slate-500 dark:text-slate-400"
    >
      © {{ new Date().getFullYear() }} DECS 2.0 Payroll System. All rights reserved.
    </footer>
  </div>
</template>
