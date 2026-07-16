@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div class="py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb / Header -->
        <div class="text-center mb-16 space-y-4">
            <div class="inline-flex items-center space-x-2 bg-vtu-primary/10 text-vtu-primary dark:text-indigo-400 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider">
                <span>🔒 Security & Consent</span>
            </div>
            <h1 class="text-4xl font-extrabold font-outfit tracking-tight text-slate-900 dark:text-white">
                Privacy Policy
            </h1>
            <p class="text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
                Last updated: {{ date('F d, Y') }}. Please read this policy to understand how we collect, use, and protect your personal information.
            </p>
        </div>

        <!-- Privacy Content -->
        <div class="bg-white dark:bg-vtu-darkCard rounded-3xl p-8 sm:p-12 border border-slate-100 dark:border-slate-800 shadow-xl space-y-10 text-slate-600 dark:text-slate-300 leading-relaxed">
            
            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">1</span>
                    Introduction
                </h2>
                <p>
                    Welcome to <strong>{{ $siteName }}</strong>. We respect your privacy and are committed to protecting the personal data of our users. This Privacy Policy describes how we collect, use, store, and share your personal information when you use our website, mobile application, virtual top-up (VTU) products, and bill payment services.
                </p>
                <p>
                    By using <strong>{{ $siteName }}</strong>, you agree to the collection and use of information in accordance with this policy. If you do not agree with any terms in this Privacy Policy, please discontinue the use of our services immediately.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">2</span>
                    Information We Collect
                </h2>
                <p>To provide our services and ensure a secure transaction environment, we collect the following categories of information:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Account Information:</strong> Name, email address, mobile phone number, login credentials, and transaction PIN.</li>
                    <li><strong>KYC Verification Data:</strong> For regulatory compliance (CBN/NDPR), we may collect verification details such as Bank Verification Number (BVN) or National Identification Number (NIN).</li>
                    <li><strong>Transaction Details:</strong> Logs of airtime purchases, data subscriptions, utility bill payments, wallet fundings, and commission earned.</li>
                    <li><strong>Payment Information:</strong> While card payments are processed securely via third-party gateways (Paystack/Flutterwave), we collect transfer references and wallet details for transaction auditing.</li>
                    <li><strong>Device & Log Data:</strong> IP addresses, browser type, operating system version, page activity, and timestamps.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">3</span>
                    How We Use Your Information
                </h2>
                <p>We process your personal information for purposes based on legitimate business interests, compliance with our legal obligations, and the performance of our contract with you. Specifically, we use your data to:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Create, manage, and secure your wallet account.</li>
                    <li>Facilitate instant delivery of VTU products (Airtime, Data, Cable, Electricity).</li>
                    <li>Verify identity in compliance with regulatory KYC requirements for financial platforms in Nigeria.</li>
                    <li>Process wallet deposits and transfer credits securely.</li>
                    <li>Send push alerts, OTP messages, transaction receipts, and system notifications.</li>
                    <li>Monitor, detect, and prevent fraudulent actions or unauthorized account access.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">4</span>
                    Sharing Your Information
                </h2>
                <p>We do not sell or rent your personal information to third parties. We only share information with partners and regulators necessary to complete transactions or comply with law:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Service Providers:</strong> Telecom operators (MTN, Airtel, Glo, 9mobile), utility companies, and exam bodies (WAEC/NECO) to deliver purchase requests.</li>
                    <li><strong>Payment Processors:</strong> Gateways like Paystack, Flutterwave, or Monnify to complete deposits and process withdrawals securely.</li>
                    <li><strong>Regulatory Authorities:</strong> Law enforcement agencies, financial crime watchdogs, or government departments when required by law or to protect user safety.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">5</span>
                    Nigeria Data Protection Regulation (NDPR) Compliance
                </h2>
                <p>
                    As a digital platform operating in Nigeria, we comply with the Nigeria Data Protection Regulation (NDPR). We implement strict operational guidelines to ensure your data is processed lawfully, transparently, and only for explicit legitimate purposes.
                </p>
                <p>
                    You have the right to request access to your personal data, request corrections, or request deletion of your information, subject to regulatory retention laws governing financial transactions.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">6</span>
                    Data Security
                </h2>
                <p>
                    We use administrative, technical, and physical security measures to help protect your personal information. These include Secure Socket Layer (SSL) encryption, transaction PIN authorizations, hashing algorithms for passwords, and firewalls on our host servers. 
                </p>
                <p>
                    While we take all reasonable precautions, please note that no system is 100% secure. You are responsible for keeping your account password and transaction PIN confidential.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">7</span>
                    Contact Us
                </h2>
                <p>If you have questions or comments about this Privacy Policy, please contact our support team:</p>
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700/80 space-y-2">
                    <p class="flex items-center gap-2 text-sm">
                        <svg class="h-4 w-4 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email: <a href="mailto:{{ $adminEmail }}" class="font-semibold text-vtu-primary hover:underline">{{ $adminEmail }}</a>
                    </p>
                    <p class="flex items-center gap-2 text-sm">
                        <svg class="h-4 w-4 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 00.099.281L10 8.583a1 1 0 00-.547.547L8.383 10.2A1 1 0 009 11.233A8.997 8.997 0 0013 15a1.003 1.003 0 001.233-.617l1.07-1.07a1 1 0 00.547-.547l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Phone Support: <span class="font-semibold">+234 903 170 4109</span>
                    </p>
                </div>
            </section>

        </div>
    </div>
</div>
@endsection
