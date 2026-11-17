<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <?= view('customer/pages/_lang_tabs', ['tabPrefix' => 'terms']) ?>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="terms-en" role="tabpanel" lang="en">
                        <h1 class="h3 fw-bold mb-2">Terms &amp; Conditions</h1>
                        <p class="text-muted">Rules for using Solqam Marketplace as a customer, seller, or merchant. Last updated 13 November 2026.</p>

                        <h5 class="fw-bold">1. The platform</h5>
                        <p class="text-secondary">Solqam Marketplace is a multi-vendor storefront in Pakistan. We list products from independent sellers, run Solqam Card for partner shops, and provide wallets, orders, and support tools. By creating an account or placing an order you agree to these terms.</p>

                        <h5 class="fw-bold">2. Accounts</h5>
                        <ul class="text-secondary">
                            <li>You must give accurate name, contact, and (for customers) gender, father/husband name, and date of birth.</li>
                            <li>Keep your password and OTP private. You are responsible for activity on your account.</li>
                            <li>Solqam may suspend accounts that break these terms, sell prohibited goods, or abuse payments.</li>
                        </ul>

                        <h5 class="fw-bold">3. Orders and payments</h5>
                        <ul class="text-secondary">
                            <li>Prices are in Pakistani Rupees (PKR). Cash on Delivery and supported online methods (including PayFast partners) may be offered at checkout.</li>
                            <li>An order is a contract between you and the seller of each item. Solqam facilitates checkout, tracking, and disputes.</li>
                            <li>Wallet cashback is credited after delivery as shown on the product, subject to our wallet rules.</li>
                        </ul>

                        <h5 class="fw-bold">4. Sellers</h5>
                        <ul class="text-secondary">
                            <li>Sellers must list genuine products, keep stock true, and dispatch on time.</li>
                            <li>Commission is deducted as published on the Commission Structure page.</li>
                            <li>COD commission and payouts follow Seller Policies and hub statements.</li>
                        </ul>

                        <h5 class="fw-bold">5. Merchants and Solqam Card</h5>
                        <ul class="text-secondary">
                            <li>Approved merchants may scan a customer Solqam Card in-shop. Credit percent and FIFO payout rules are shown in Merchant Hub.</li>
                            <li>Merchant payments to Solqam require a screenshot and admin approval before customer wallets are released.</li>
                            <li>The Solqam Card is an identity and credit instrument on our network, not a bank Visa/Mastercard.</li>
                        </ul>

                        <h5 class="fw-bold">6. Returns</h5>
                        <p class="text-secondary">Delivered orders may be returned as described in <a href="<?= site_url('returns-policy') ?>">Returns &amp; Refunds</a>. Approved refunds are credited to the Solqam wallet unless we state otherwise.</p>

                        <h5 class="fw-bold">7. Liability</h5>
                        <p class="text-secondary">We work to keep the site available and secure. We are not liable for seller stock errors, courier delays outside our control, or losses beyond the order value we processed. Pakistani law applies.</p>

                        <h5 class="fw-bold">8. Changes</h5>
                        <p class="text-secondary mb-0">We may update these terms. Continued use of Solqam after a change means you accept the new version. Questions: <a href="<?= site_url('help') ?>">Help Center</a> or <a href="mailto:info@solqam.com">info@solqam.com</a>.</p>
                    </div>

                    <div class="tab-pane fade" id="terms-ur" role="tabpanel" lang="ur" dir="rtl">
                        <h1 class="h3 fw-bold mb-2">شرائط و ضوابط</h1>
                        <p class="text-muted">سولقام مارکیٹ پلیس کو کسٹمر، سیلر یا مرچنٹ کی حیثیت سے استعمال کرنے کے قواعد۔ آخری اپ ڈیٹ ۱۳ نومبر ۲۰۲۶۔</p>

                        <h5 class="fw-bold">۱۔ پلیٹ فارم</h5>
                        <p class="text-secondary">سولقام مارکیٹ پلیس پاکستان میں ایک ملٹی وینڈر اسٹور ہے۔ ہم آزاد سیلرز کی مصنوعات دکھاتے ہیں، پارٹنر دکانوں کے لیے سولقام کارڈ چلاتے ہیں، اور والیٹ، آرڈرز اور سپورٹ کے ٹولز دیتے ہیں۔ اکاؤنٹ بنانے یا آرڈر دینے سے آپ ان شرائط سے اتفاق کرتے ہیں۔</p>

                        <h5 class="fw-bold">۲۔ اکاؤنٹس</h5>
                        <ul class="text-secondary">
                            <li>درست نام اور رابطہ دیں؛ کسٹمر کے لیے جنس، والد/شوہر کا نام اور تاریخ پیدائش بھی درکار ہے۔</li>
                            <li>پاس ورڈ اور او ٹی پی خفیہ رکھیں۔ آپ کے اکاؤنٹ کی سرگرمی کی ذمہ داری آپ پر ہے۔</li>
                            <li>سولقام ان اکاؤنٹس کو معطل کر سکتا ہے جو یہ شرائط توڑیں، ممنوعہ سامان بیچیں، یا ادائیگیوں کا غلط استعمال کریں۔</li>
                        </ul>

                        <h5 class="fw-bold">۳۔ آرڈرز اور ادائیگیاں</h5>
                        <ul class="text-secondary">
                            <li>قیمتیں پاکستانی روپے (PKR) میں ہیں۔ چیک آؤٹ پر کیش آن ڈیلیوری اور سپورٹڈ آن لائن طریقے (بشمول پے فاسٹ پارٹنرز) دستیاب ہو سکتے ہیں۔</li>
                            <li>ہر آئٹم کا معاہدہ آپ اور اس کے سیلر کے درمیان ہے۔ سولقام چیک آؤٹ، ٹریکنگ اور تنازعات میں سہولت دیتا ہے۔</li>
                            <li>والیٹ کیش بیک ڈیلیوری کے بعد پروڈکٹ پر دکھائے گئے حساب سے ملتا ہے، والیٹ کے قواعد کے مطابق۔</li>
                        </ul>

                        <h5 class="fw-bold">۴۔ سیلرز</h5>
                        <ul class="text-secondary">
                            <li>سیلر اصلی مصنوعات لسٹ کریں، سٹاک درست رکھیں، اور وقت پر ڈسپچ کریں۔</li>
                            <li>کمیشن کمیشن سٹرکچر والے صفحے کے مطابق کاٹی جاتی ہے۔</li>
                            <li>سی او ڈی کمیشن اور ادائیگیاں سیلر پالیسیز اور ہب سٹیٹمنٹس کے مطابق ہیں۔</li>
                        </ul>

                        <h5 class="fw-bold">۵۔ مرچنٹس اور سولقام کارڈ</h5>
                        <ul class="text-secondary">
                            <li>منظور شدہ مرچنٹ دکان میں کسٹمر کا سولقام کارڈ اسکین کر سکتے ہیں۔ کریڈٹ فیصد اور FIFO ادائیگی کے قواعد مرچنٹ ہب میں دکھائے جاتے ہیں۔</li>
                            <li>سولقام کو مرچنٹ کی ادائیگی کے لیے اسکرین شاٹ اور ایڈمن کی منظوری ضروری ہے، اس کے بعد کسٹمر والیٹ جاری ہوتا ہے۔</li>
                            <li>سولقام کارڈ ہمارے نیٹ ورک پر شناخت اور کریڈٹ کا ذریعہ ہے، بینک کا ویزا/ماسٹر کارڈ نہیں۔</li>
                        </ul>

                        <h5 class="fw-bold">۶۔ واپسی</h5>
                        <p class="text-secondary">ڈیلیور شدہ آرڈرز <a href="<?= site_url('returns-policy') ?>">ریٹرنز اینڈ ریفنڈز</a> کے مطابق واپس ہو سکتے ہیں۔ منظور شدہ رقم سولقام والیٹ میں جمع ہوتی ہے، جب تک ہم کچھ اور نہ کہیں۔</p>

                        <h5 class="fw-bold">۷۔ ذمہ داری</h5>
                        <p class="text-secondary">ہم سائٹ کو دستیاب اور محفوظ رکھنے کی کوشش کرتے ہیں۔ سیلر کے سٹاک کی غلطی، ہمارے قابو سے باہر کورئیر تاخیر، یا پروسیس شدہ آرڈر کی مالیت سے زیادہ نقصان کی ذمہ داری ہم پر نہیں۔ پاکستانی قانون لاگو ہے۔</p>

                        <h5 class="fw-bold">۸۔ تبدیلیاں</h5>
                        <p class="text-secondary mb-0">ہم یہ شرائط اپ ڈیٹ کر سکتے ہیں۔ تبدیلی کے بعد سولقام کا استعمال نئے ورژن کی قبولیت ہے۔ سوالات: <a href="<?= site_url('help') ?>">ہیلپ سینٹر</a> یا <a href="mailto:info@solqam.com">info@solqam.com</a>۔</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
