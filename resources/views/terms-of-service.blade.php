@extends('layouts.app')

@section('title', 'Terms of Service')

@section('content')
<div class="py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb / Header -->
        <div class="text-center mb-16 space-y-4">
            <div class="inline-flex items-center space-x-2 bg-vtu-primary/10 text-vtu-primary dark:text-indigo-400 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider">
                <span>📋 Service Delivery Terms</span>
            </div>
            <h1 class="text-4xl font-extrabold font-outfit tracking-tight text-slate-900 dark:text-white">
                Terms of Service
            </h1>
            <p class="text-slate-500 dark:text-slate-400 max-w-xl mx-auto">
                Last updated: {{ date('F d, Y') }}. Please read these terms carefully to understand our service level commitments and expectations.
            </p>
        </div>

        <!-- Terms Content -->
        <div class="bg-white dark:bg-vtu-darkCard rounded-3xl p-8 sm:p-12 border border-slate-100 dark:border-slate-800 shadow-xl space-y-10 text-slate-600 dark:text-slate-300 leading-relaxed">
            
            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">1</span>
                    Scope of Services
                </h2>
                <p>
                    <strong>{{ $siteName }}</strong> provides virtual top-up (VTU) services including airtime loading, internet data bundle purchases, electricity token generations, exam scratch card PINs, and cable television subscription renewals. 
                </p>
                <p>
                    We act as a secure intermediary agent between you and telecom operators (MTN, Glo, Airtel, 9mobile), electricity distribution companies (DISCOs), and multi-choice cable networks. Our service is automated and relies on direct APIs of these primary service providers.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">2</span>
                    Service Uptime & SLA Commitments
                </h2>
                <p>
                    We aim to keep our VTU delivery channels operational 24/7. However, temporary outages or delays can occur due to cellular network failures, gateway latency, or third-party server updates.
                </p>
                <p>
                    In cases where a transaction is debited from your wallet but status is returned as pending or failed, our system will automatically perform a retrying request or initiate a wallet refund within 5 to 30 minutes. If a transaction remains unresolved, please contact our support team with your transaction reference.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">3</span>
                    Developer & API Usage
                </h2>
                <p>For users integrating their reseller platforms or custom websites via our developer API:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>You are granted a non-exclusive, non-transferable, revocable license to access our APIs strictly to complete utility purchases.</li>
                    <li>You must maintain server-side confidentiality of your secret API tokens.</li>
                    <li>You must not execute rapid looping calls or coordinate denial-of-service (DDoS) requests that degrade our server performance. Abuse of API calls will lead to immediate API token blacklisting.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">4</span>
                    Prohibited Activities
                </h2>
                <p>You agree not to use the platform to:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Launder funds or utilize cards/bank accounts obtained via cybercrime or phishing activities.</li>
                    <li>Attempt to bypass security constraints, scan ports, or probe for vulnerabilities.</li>
                    <li>Conduct automated scraping, spoofing requests, or database injection attempts.</li>
                    <li>Create multiple accounts to exploit sign-up coupons or referral programs.</li>
                </ul>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">5</span>
                    Account Termination & Suspension
                </h2>
                <p>
                    We reserve the right to block, suspend, or permanently delete your account and forfeit wallet funds if we find you have violated these Terms of Service, engaged in fraudulent activities, or breached anti-money laundering regulations.
                </p>
                <p>
                    You can deactivate your account at any time from your settings panel, provided your wallet balance is zero and there are no ongoing transaction disputes.
                </p>
            </section>

            <section class="space-y-4">
                <h2 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="h-8 w-8 rounded-lg bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-sm font-bold">6</span>
                    Changes to the Service
                </h2>
                <p>
                    We reserve the right to modify, change pricing rates, or discontinue any portion of our VTU services without prior notice. Discounts, commissions, and API provider lists are updated dynamically based on operator wholesale rates.
                </p>
            </section>

        </div>
    </div>
</div>
@endsection
