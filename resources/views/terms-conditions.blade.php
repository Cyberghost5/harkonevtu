@extends('layouts.app')

@section('title', 'Terms & Conditions')

@section('content')
<div class="py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb / Header -->
        <div class="text-center mb-16 space-y-4">
            <div class="inline-flex items-center space-x-2 bg-vtu-primary/10 text-vtu-primary dark:text-indigo-400 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider">
                <span>⚖️ Legal Agreement</span>
            </div>
            <h1 class="text-4xl font-extrabold font-outfit tracking-tight text-slate-900 dark:text-white">
                Terms & Conditions
            </h1>
            <p class="text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
                Last updated: {{ date('F d, Y') }}. Please read these terms carefully before accessing or using our services.
            </p>
        </div>

        <!-- Terms Content -->
        <div class="bg-white dark:bg-vtu-darkCard rounded-3xl p-8 sm:p-12 border border-slate-100 dark:border-slate-800 shadow-xl space-y-10 text-slate-600 dark:text-slate-300 leading-relaxed">
            
            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">1</span>
                    Agreement to Terms
                </h2>
                <p>
                    These Terms and Conditions constitute a legally binding agreement made between you, whether personally or on behalf of an entity ("you") and <strong>{{ $siteName }}</strong> ("we", "us", or "our"), concerning your access to and use of our website, mobile application, virtual top-up platform, and other services.
                </p>
                <p>
                    By registering an account and using our services, you acknowledge that you have read, understood, and agreed to be bound by all of these Terms and Conditions. If you do not agree with all of these terms, you are expressly prohibited from using our services and must discontinue use immediately.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">2</span>
                    Account Eligibility & Security
                </h2>
                <p>To use our platform, you must meet the following criteria:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>You must provide accurate, current, and complete registration details.</li>
                    <li>You are responsible for safeguarding your login credentials, including password and 4-digit transaction PIN.</li>
                    <li>Any transaction authorized with your PIN will be deemed authorized by you. We are not liable for any losses arising from compromise of your PIN.</li>
                    <li>You must immediately notify us if you suspect any unauthorized access to your account.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">3</span>
                    Wallet Operations & Funding
                </h2>
                <p>Your wallet is a virtual ledger denominated in Nigerian Naira (₦) used solely for purchasing VTU services on our platform:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Wallet Deposits:</strong> You can fund your wallet via automatic bank transfer (DVA), manual deposit, or online payment gateways. Deposits are typically instant but subject to bank network clearance.</li>
                    <li><strong>Refunds:</strong> Wallet deposits are non-refundable. Funds can only be utilized for services on the platform. We do not support wallet withdrawals back to bank accounts except in cases of authorized merchant settlements.</li>
                    <li><strong>Suspicious Activity:</strong> If we detect fraudulent funding or identity mismatches, we reserve the right to temporarily freeze your wallet and hold funds pending verification.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">4</span>
                    VTU Products & Bill Payments
                </h2>
                <p>We facilitate automated purchases of digital utilities. Please note the following delivery rules:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong>Accuracy:</strong> You are solely responsible for ensuring the correctness of input values, including phone numbers for airtime/data, utility meter numbers, and cable smartcard numbers.</li>
                    <li><strong>Non-Reversibility:</strong> Transactions completed with the correct API response from network operators cannot be cancelled, reversed, or refunded under any circumstances.</li>
                    <li><strong>Service Uptime:</strong> We depend on telecom providers and grid distributors for product delivery. While we guarantee our API is functional, we are not responsible for delays caused by external network downtime.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">5</span>
                    KYC Compliance
                </h2>
                <p>
                    In accordance with Central Bank of Nigeria (CBN) regulations and anti-money laundering (AML) acts, we implement tiered KYC limits. Depending on your transaction volume, you may be required to verify your BVN, NIN, or valid identification. 
                </p>
                <p>
                    We use secure API services to validate KYC details. Providing falsified information constitutes a breach of contract and may result in permanent termination of your account.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">6</span>
                    Limitation of Liability
                </h2>
                <p>
                    In no event will <strong>{{ $siteName }}</strong>, our directors, employees, or agents be liable to you or any third party for any direct, indirect, consequential, exemplary, incidental, special, or punitive damages, including lost profit, lost revenue, loss of data, or other damages arising from your use of our platform.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">7</span>
                    Governing Law
                </h2>
                <p>
                    These Terms and Conditions and your use of the platform are governed by and construed in accordance with the laws of the Federal Republic of Nigeria, without regard to its conflict of law principles.
                </p>
            </section>

        </div>
    </div>
</div>
@endsection
