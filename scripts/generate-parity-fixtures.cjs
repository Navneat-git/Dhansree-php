// Generates cross-language parity fixtures using the Dhansree API's OWN compiled modules,
// so the PHP port is tested against the real implementation, not a re-description of it.
//
// Usage:  node scripts/generate-parity-fixtures.cjs > tests/Fixtures/node-parity.json
// The Node application must have been built first (`npm run build` there), because this reads
// apps/api/dist. Point NODE_APP_ROOT at it if it is not a sibling directory named "Dhansree".
const { createRequire } = require('node:module');
const path = require('node:path');
const crypto = require('node:crypto');

const ROOT = process.env.NODE_APP_ROOT || path.resolve(__dirname, '../../Dhansree');
const req = createRequire(path.join(ROOT, 'package.json'));

const totp = require(path.join(ROOT, 'apps/api/dist/auth/totp.js'));
const { SecretBox } = require(path.join(ROOT, 'apps/api/dist/auth/secret-box.js'));
const { ARGON2_PARAMS } = require(path.join(ROOT, 'apps/api/dist/auth/password-hasher.js'));
const { hash } = req('@node-rs/argon2');
const sig = require(path.join(ROOT, 'apps/api/dist/payments/razorpay/razorpay-signature.js'));

(async () => {
  const out = { generatedBy: 'apps/api/dist (real compiled modules)', argon2Params: ARGON2_PARAMS };

  // --- Argon2id: hashes produced by the real hasher config -------------------
  out.passwords = [];
  for (const pw of ['CorrectHorseBattery7!', 'a-very-long-passphrase-with-émojis-🔐-and-spaces', 'Zz9!'.repeat(8)]) {
    out.passwords.push({ password: pw, hash: await hash(pw, { ...ARGON2_PARAMS, algorithm: 2 }) });
  }

  // --- SecretBox: sealed with the REAL SecretBox service ---------------------
  const key = crypto.randomBytes(32).toString('base64');
  const box = new SecretBox({ get: () => key });
  out.secretBox = { key, version: 'v1', cases: [] };
  for (const [plain, label] of [
    ['JBSWY3DPEHPK3PXP', 'admin-totp:42'],
    ['GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'admin-totp:1'],
    ['short', 'admin-totp:999'],
  ]) {
    out.secretBox.cases.push({ plaintext: plain, label, sealed: box.seal(plain, label) });
  }

  // --- TOTP: codes from the real totpCode ------------------------------------
  out.totp = { digits: totp.TOTP_DIGITS, period: totp.TOTP_PERIOD_SECONDS, window: totp.TOTP_WINDOW_STEPS, cases: [] };
  for (const secret of ['JBSWY3DPEHPK3PXP', 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'MFRGGZDFMZTWQ2LK']) {
    for (const step of [0, 1, 59, 1111111109, 1234567890, 2000000000, 37037036]) {
      out.totp.cases.push({ secret, step, code: totp.totpCode(secret, step) });
    }
  }

  // --- Razorpay signatures from the real helpers ----------------------------
  out.razorpay = {
    paymentVerification: [
      { providerOrderId: 'order_ABC123', providerPaymentId: 'pay_XYZ789', keySecret: 'test_secret_value',
        signature: sig.checkoutSignature('order_ABC123', 'pay_XYZ789', 'test_secret_value') },
    ],
    webhook: [
      { rawBody: '{"event":"payment.captured","payload":{}}', webhookSecret: 'wh_secret_value',
        signature: sig.webhookSignature(Buffer.from('{"event":"payment.captured","payload":{}}', 'utf8'), 'wh_secret_value') },
    ],
  };

  console.log(JSON.stringify(out, null, 2));
})().catch((e) => { console.error('FAILED:', e.message); process.exit(1); });
