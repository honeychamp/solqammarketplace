<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <?= view('customer/pages/_lang_tabs', ['tabPrefix' => 'privacy']) ?>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="privacy-en" role="tabpanel" lang="en">
                        <h1 class="h3 fw-bold mb-2">Privacy Policy</h1>
                        <p class="text-muted">How Solqam Marketplace collects, uses, and protects personal data. Last updated 13 November 2026.</p>
                        <h5 class="fw-bold">Who we are</h5>
                        <p class="text-secondary">Solqam Marketplace (solqam.com) is an online marketplace in Pakistan. This Privacy Policy applies to shoppers, sellers, and visitors who use our website or sign in with Google or Facebook.</p>
                        <h5 class="fw-bold">Information we collect</h5>
                        <ul class="text-secondary">
                            <li>Account details: name, email, phone, password hash, and delivery addresses.</li>
                            <li>Customer profile: gender, father/husband name, and date of birth when you register as a customer.</li>
                            <li>If you continue with Google or Facebook: your public name, email, and profile photo, plus the provider’s user id.</li>
                            <li>Orders, payments status, wallet ledger entries, and customer-care messages.</li>
                            <li>Device and log data such as IP address and pages viewed, used to keep the store secure.</li>
                        </ul>
                        <h5 class="fw-bold">How we use information</h5>
                        <ul class="text-secondary">
                            <li>To create your account, process orders, and provide seller tools.</li>
                            <li>To prevent fraud, enforce our policies, and contact you about your orders.</li>
                            <li>We do not sell your personal information.</li>
                        </ul>
                        <h5 class="fw-bold">Sharing</h5>
                        <p class="text-secondary">Sellers see the shipping details needed to fulfill your order. Payment partners receive what they need to process a payment. We may share data if required by Pakistani law.</p>
                        <h5 class="fw-bold">Facebook and Google login</h5>
                        <p class="text-secondary">When you use Continue with Facebook or Google, those companies share the profile fields you approve. You can disconnect that login by using email sign-in instead and asking us to unlink the social id.</p>
                        <h5 class="fw-bold">Retention and security</h5>
                        <p class="text-secondary">We keep account and order records as long as the account is active and as required for accounting. Access is limited to people who operate Solqam.</p>
                        <h5 class="fw-bold">Your choices</h5>
                        <p class="text-secondary mb-0">You may request a copy or deletion of your account data. See our <a href="<?= site_url('data-deletion') ?>">User data deletion</a> page, or contact us from the <a href="<?= site_url('help') ?>">Help Center</a>.</p>
                    </div>

                    <div class="tab-pane fade" id="privacy-ur" role="tabpanel" lang="ur" dir="rtl">
                        <h1 class="h3 fw-bold mb-2">رازداری کی پالیسی</h1>
                        <p class="text-muted">سولقام مارکیٹ پلیس ذاتی معلومات کیسے جمع، استعمال اور محفوظ کرتا ہے۔ آخری اپ ڈیٹ ۱۳ نومبر ۲۰۲۶۔</p>
                        <h5 class="fw-bold">ہم کون ہیں</h5>
                        <p class="text-secondary">سولقام مارکیٹ پلیس (solqam.com) پاکستان میں آن لائن مارکیٹ پلیس ہے۔ یہ پالیسی خریداروں، سیلرز اور ان وزٹرز پر لاگو ہے جو ہماری ویب سائٹ استعمال کریں یا گوگل / فیس بک سے سائن ان کریں۔</p>
                        <h5 class="fw-bold">ہم کون سی معلومات لیتے ہیں</h5>
                        <ul class="text-secondary">
                            <li>اکاؤنٹ: نام، ای میل، فون، پاس ورڈ ہیش، اور ڈیلیوری ایڈریس۔</li>
                            <li>کسٹمر پروفائل: جنس، والد/شوہر کا نام، اور تاریخ پیدائش جب آپ کسٹمر کے طور پر رجسٹر ہوں۔</li>
                            <li>گوگل یا فیس بک سے جاری رکھیں تو: پبلک نام، ای میل، پروفائل فوٹو، اور فراہم کنندہ کی یوزر آئی ڈی۔</li>
                            <li>آرڈرز، ادائیگی کی حیثیت، والیٹ لیجر، اور کسٹمر کیئر پیغامات۔</li>
                            <li>ڈیوائس اور لاگ ڈیٹا جیسے آئی پی ایڈریس اور دیکھے گئے صفحات، سٹور کی حفاظت کے لیے۔</li>
                        </ul>
                        <h5 class="fw-bold">استعمال کیسے کرتے ہیں</h5>
                        <ul class="text-secondary">
                            <li>اکاؤنٹ بنانے، آرڈر پروسیس کرنے، اور سیلر ٹولز دینے کے لیے۔</li>
                            <li>دھوکہ دہی روکنے، پالیسی نافذ کرنے، اور آپ کے آرڈر کے بارے میں رابطہ کرنے کے لیے۔</li>
                            <li>ہم آپ کی ذاتی معلومات فروخت نہیں کرتے۔</li>
                        </ul>
                        <h5 class="fw-bold">شیئرنگ</h5>
                        <p class="text-secondary">سیلر کو شپنگ کی وہ تفصیل دکھتی ہے جو آرڈر پورا کرنے کے لیے ضروری ہے۔ ادائیگی کے پارٹنرز کو صرف ادائیگی کے لیے درکار ڈیٹا ملتا ہے۔ پاکستانی قانون کے تحت ڈیٹا شیئر کیا جا سکتا ہے۔</p>
                        <h5 class="fw-bold">فیس بک اور گوگل لاگ اِن</h5>
                        <p class="text-secondary">جب آپ فیس بک یا گوگل سے جاری رکھتے ہیں تو وہ کمپنیاں وہ فیلڈز بھیجتی ہیں جن کی آپ اجازت دیں۔ ای میل سے سائن اِن کر کے اور سوشل آئی ڈی ان لنک کروا کر یہ لاگ اِن ختم کیا جا سکتا ہے۔</p>
                        <h5 class="fw-bold">رکھنے کی مدت اور سیکیورٹی</h5>
                        <p class="text-secondary">اکاؤنٹ جب تک فعال ہے اور اکاؤنٹنگ کے لیے جتنی دیر لازم ہو، ریکارڈ رکھتے ہیں۔ رسائی صرف سولقام چلانے والے لوگوں تک محدود ہے۔</p>
                        <h5 class="fw-bold">آپ کے اختیارات</h5>
                        <p class="text-secondary mb-0">آپ اپنے اکاؤنٹ ڈیٹا کی کاپی یا ڈیلیٹ کی درخواست کر سکتے ہیں۔ <a href="<?= site_url('data-deletion') ?>">یوزر ڈیٹا ڈیلیشن</a> صفحہ دیکھیں، یا <a href="<?= site_url('help') ?>">ہیلپ سینٹر</a> سے رابطہ کریں۔</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
