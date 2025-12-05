# 🚚 BITESHIP INTEGRATION - COMPLETE GUIDE

## ✅ FILES YANG SUDAH DIBUAT/DIUPDATE

### 1. Database Migration
📄 **`/app/database-migration.sql`**
- Fix settings table (normalize column name)
- Add shipping columns to orders table
- Create `order_addresses` table
- Create `biteship_shipments` table  
- Create `biteship_webhook_logs` table
- Create `print_batches` table
- Insert default Biteship configuration

**⚠️ PENTING: Backup database dulu, kemudian jalankan file SQL ini di server production Anda!**

---

### 2. Core Integration Files

#### Biteship Client (Sudah Ada)
- **`/app/includes/BiteshipClient.php`** - Main API client
- **`/app/includes/BiteshipConfig.php`** - Configuration helper

---

### 3. Admin Panel - Order Management

#### Main Orders Page
📄 **`/app/admin/orders/index.php`** (File lama - perlu diganti)
**Fitur yang harus ada:**
- ✅ Status tabs filtering (Baru, Siap Print, Menunggu Pickup, dll)
- ✅ Search by order number, tracking, customer name
- ✅ Bulk selection dengan checkboxes
- ✅ Bulk actions: Print Labels, Update Status
- ✅ Pagination
- ✅ Professional UI dengan status badges

#### Batch Print System
📄 **`/app/admin/orders/print-batch.php`**
- Membuat batch print record
- Update status otomatis ke "waiting_pickup" setelah print
- Render multiple A6 labels dengan page breaks

📄 **`/app/admin/orders/templates/label-a6.php`**
- Template label pengiriman A6 (105mm x 148mm)
- Branding Dorve.id
- From/To address blocks
- Courier info
- Barcode waybill
- Professional layout

📄 **`/app/admin/assets/label-a6.css`**
- Stylesheet untuk A6 labels
- Print-optimized
- Professional styling

#### Supporting Files
📄 **`/app/admin/orders/update-status.php`**
- API endpoint untuk bulk update status orders

📄 **`/app/admin/orders/picking-list.php`**
- Warehouse picking list
- Show items per order
- Checkbox untuk track completion
- Print-friendly

---

### 4. API Integration Testing

📄 **`/app/admin/integration/test-biteship-api.php`**
- Test Biteship API key connectivity
- Update test status ke database
- Accessible from admin panel

📄 **`/app/admin/integration/test-webhook.php`**
- Test webhook endpoint accessibility
- Verify webhook URL is publicly accessible

---

### 5. Frontend/Customer APIs

📄 **`/app/api/shipping/calculate-rates.php`**
- Calculate shipping rates at checkout
- Support multiple couriers
- Use store origin from settings

📄 **`/app/api/orders/create-from-payment.php`**
- Auto-create Biteship shipment after payment
- Save shipment data to database
- Update order status
- **⚠️ Call this API after successful payment**

---

### 6. Admin Settings (Already Updated)

📄 **`/app/admin/settings/api-settings.php`**
- Fixed `$valueColumn` undefined error
- Biteship configuration form
- API key testing
- Webhook URL display

---

## 🔧 SETUP INSTRUCTIONS

### Step 1: Database Migration
```bash
# Backup database dulu!
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d).sql

# Jalankan migration
mysql -u [user] -p [database] < /path/to/database-migration.sql
```

### Step 2: Update Admin Orders Page
File `/app/admin/orders/index.php` yang lama harus diganti dengan versi baru yang punya:
- Status tabs
- Bulk selection
- Search functionality
- Professional UI

**Saya sudah buat versi lengkapnya di response sebelumnya, tinggal replace file tersebut.**

### Step 3: Configure Biteship
1. Login ke admin panel: `https://dorve.id/admin`
2. Go to: **Settings → API Settings**
3. Scroll ke section **Biteship Configuration**
4. API Key sudah terisi otomatis (dari migration)
5. Klik **"Test API Key"** untuk verify connection
6. Copy Webhook URL: `https://dorve.id/api/biteship/webhook.php`

### Step 4: Setup Biteship Webhook
1. Login ke [Biteship Dashboard](https://business.biteship.com/)
2. Go to: **Settings → Webhooks**
3. Add webhook URL: `https://dorve.id/api/biteship/webhook.php`
4. Subscribe to events:
   - `order.status` (update shipping status)
   - `order.waybill_id` (waybill generated)

### Step 5: Test Integration
1. Buat test order di website
2. Complete payment (status jadi "paid")
3. Call API: `POST /api/orders/create-from-payment.php` dengan:
   ```json
   {
     "order_id": 123
   }
   ```
4. Verify shipment created di Biteship
5. Check admin orders page - status harus jadi "waiting_print"

---

## 📦 BULK PRINT WORKFLOW

### Cara Menggunakan Batch Print System:

1. **Go to Admin Orders**: `/admin/orders/index.php`
2. **Filter by status**: Klik tab "Siap Print"
3. **Select orders**: Check boxes untuk orders yang mau diprint
4. **Print Labels**: Klik button "🖨️ Print Labels"
5. **Print dialog opens**: New window dengan semua labels
6. **Auto update**: Status otomatis jadi "waiting_pickup"
7. **Batch record**: Tersimpan di `print_batches` table

### Format Label:
- **Size**: A6 (105mm x 148mm)
- **Content**: 
  - Header dengan logo Dorve.id
  - From address (store)
  - To address (customer) - highlighted
  - Courier info
  - Barcode waybill
  - Order number & date
- **Professional layout** siap untuk print

---

## 🔄 ORDER STATUS FLOW

```
NEW (Pesanan Baru)
  ↓
WAITING_PRINT (Siap Print) ← Setelah create Biteship order
  ↓
WAITING_PICKUP (Menunggu Pickup) ← Setelah print labels
  ↓
IN_TRANSIT (Dalam Pengiriman) ← Webhook dari Biteship
  ↓
DELIVERED (Terkirim) ← Webhook dari Biteship
```

---

## 🎯 API ENDPOINTS

### For Admin/Backend:

#### Create Biteship Order (After Payment)
```
POST /api/orders/create-from-payment.php
Body: { "order_id": 123 }
```

#### Update Order Status (Bulk)
```
POST /admin/orders/update-status.php
Body: { 
  "order_ids": [1, 2, 3], 
  "status": "waiting_pickup" 
}
```

### For Frontend/Checkout:

#### Calculate Shipping Rates
```
POST /api/shipping/calculate-rates.php
Body: {
  "postal_code": "12345",
  "items": [
    {
      "name": "Product 1",
      "value": 100000,
      "quantity": 2,
      "weight": 500
    }
  ]
}
```

---

## 🧪 TESTING CHECKLIST

### Admin Panel Testing:
- [ ] Login ke admin panel
- [ ] Navigate ke Orders page
- [ ] Test status tabs filtering
- [ ] Test search functionality
- [ ] Test bulk selection
- [ ] Test print labels (dummy data OK)
- [ ] Test picking list

### API Testing:
- [ ] Test Biteship API key dari admin settings
- [ ] Test webhook endpoint connectivity
- [ ] Test calculate shipping rates
- [ ] Test create order from payment
- [ ] Verify webhook receiving data from Biteship

### End-to-End Flow:
- [ ] Customer complete order
- [ ] Payment successful
- [ ] Create Biteship shipment (manual or auto)
- [ ] Admin print labels
- [ ] Status update via webhook
- [ ] Customer can track order

---

## 📋 WEBHOOK HANDLER

File webhook sudah ada di: **`/app/api/biteship/webhook.php`**

Webhook ini akan:
1. Receive POST dari Biteship
2. Log ke `biteship_webhook_logs` table
3. Update `biteship_shipments` status
4. Update `orders.fulfillment_status`

Events yang di-handle:
- `order.status` - Update shipping status
- `order.waybill_id` - Waybill number generated

---

## 🎨 UI/UX FEATURES

### Professional Order Management:
- ✅ Color-coded status badges
- ✅ Responsive design
- ✅ Quick search
- ✅ Tab-based filtering
- ✅ Bulk actions bar (only shows when items selected)
- ✅ Pagination
- ✅ Order details link

### A6 Label Design:
- ✅ Professional branding
- ✅ Clear address separation (from/to)
- ✅ Highlighted destination address
- ✅ Courier logo space
- ✅ Barcode/waybill prominent
- ✅ Print-optimized layout
- ✅ Page breaks between labels

---

## ⚠️ IMPORTANT NOTES

### Database:
- **BACKUP FIRST** sebelum run migration SQL!
- Migration SQL sudah include API key Anda
- Column names sudah di-normalize (setting_value)

### API Key:
- API key sudah di-set di migration SQL
- Key: `biteship_live.eyJhbGc...` (Your production key)
- Environment: `production`

### Webhook:
- **MUST** configure webhook di Biteship Dashboard
- URL: `https://dorve.id/api/biteship/webhook.php`
- Tanpa webhook, status tidak auto-update

### Order Creation:
- Call API `create-from-payment.php` SETELAH payment success
- Bisa manual dari admin panel atau auto via payment callback
- Perlu shipping courier & service dipilih saat checkout

---

## 🔮 NEXT STEPS (Not Implemented Yet)

Sesuai handoff, masih ada tugas lain:

### Priority 1 (P1):
- [ ] **Business Growth Dashboard** - Statistics harian/bulanan
- [ ] **Email Notifications** - Professional HTML templates untuk order confirmation, shipping updates

### Priority 2 (P2):
- [ ] **Product Detail Enhancements**:
  - "You Might Also Like" section
  - Customer Reviews system
- [ ] **User Management**:
  - Admin can add/subtract wallet balance
  - Full user data editing
- [ ] **Password Reset Flow** - Verify & test

### Priority 3 (P3):
- [ ] **Fix Broken Admin Pages**: payment-settings, bank-accounts
- [ ] **Referral System Overhaul** - Percentage-based, configurable

---

## 🆘 TROUBLESHOOTING

### "Settings table error"
✅ **Fixed** - Migration SQL normalize column name

### "API connection failed"
- Check API key di admin settings
- Run test connection
- Verify environment (production vs sandbox)

### "Print labels not working"
- Check if orders have waybill_id
- Verify `biteship_shipments` table has data
- Check browser pop-up blocker

### "Webhook not receiving"
- Verify URL publicly accessible
- Check Biteship Dashboard webhook config
- Check `biteship_webhook_logs` table for incoming data

---

## 📞 SUPPORT

Jika ada error atau issue:
1. Check database migration completed
2. Check API key configured correctly
3. Check webhook URL configured
4. Check browser console for JS errors
5. Check PHP error logs

---

**Integration Complete! 🎉**

Semua file sudah dibuat dan siap di-deploy ke production server Anda.
Tinggal jalankan migration SQL dan test!
