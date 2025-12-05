# 🚀 Dorve House - Database Restoration Guide

## 📋 Overview
Complete database restoration untuk Dorve House e-commerce platform dengan semua fitur lengkap dan siap pakai.

---

## ✅ Yang Sudah Disiapkan

### 1. **File SQL Lengkap**
- **File:** `COMPLETE-DATABASE-RESTORE.sql`
- **Size:** ~800 lines
- **Content:** 
  - 20 Tables (Users, Products, Orders, dll)
  - Stored Procedures (Referral & Tier System)
  - Triggers (Auto-update stock & timeline)
  - Views (Order tracking, Product inventory)
  - Sample data siap pakai

### 2. **Script Auto-Import**
- **File:** `restore-database.php`
- **Features:**
  - Web-based interface dengan progress bar
  - Auto-execute SQL file
  - Error handling & logging
  - Success/failure notification
  - Direct links ke admin & homepage

---

## 🎯 Cara Menggunakan

### **Metode 1: Auto-Import (Recommended) ✨**

1. **Buka browser** dan akses:
   ```
   https://dorve.co/restore-database.php
   ```
   atau
   ```
   http://your-domain.com/restore-database.php
   ```

2. **Review informasi** database yang akan di-restore

3. **Klik tombol** "🚀 Start Database Restoration"

4. **Konfirmasi** warning (semua data lama akan dihapus)

5. **Wait** sampai proses selesai (sekitar 30-60 detik)

6. **Success!** Database sudah ter-restore lengkap

7. **Login** ke admin panel dengan credentials:
   - Admin 1: `admin1@dorve.co` / `Dorve889`
   - Admin 2: `admin2@dorve.co` / `Admin889`

### **Metode 2: Manual Import (Alternative)**

Jika auto-import tidak bekerja, gunakan phpMyAdmin:

1. **Login** ke phpMyAdmin (cPanel → phpMyAdmin)

2. **Pilih** database: `dorve_dorve`

3. **Klik tab** "Import"

4. **Choose file:** pilih `COMPLETE-DATABASE-RESTORE.sql`

5. **Klik** "Go" atau "Import"

6. **Wait** sampai selesai

7. **Done!** Database ter-restore

---

## 📊 Database Structure

### **Core Tables (20 Tables)**

#### 👥 User Management
- `users` - User accounts (customers & admins)
- `addresses` - Customer shipping addresses

#### 🛍️ Product Management
- `categories` - Product categories
- `products` - Main product table
- `product_variants` - Size & color variants with stock
- `reviews` - Product reviews

#### 🛒 Order Management
- `orders` - Main orders table
- `order_items` - Order line items
- `order_timeline` - Order status tracking
- `order_vouchers` - Vouchers used in orders

#### 💰 Wallet & Payment
- `wallet_transactions` - Wallet transaction history
- `topups` - Wallet topup records

#### 🎁 Referral & Loyalty
- `referral_rewards` - Referral commission tracking
- `referral_settings` - Referral system configuration
- `tier_upgrades` - Customer tier upgrade history

#### 🎟️ Promotions
- `vouchers` - Discount & free shipping vouchers
- `shipping_methods` - Available shipping options

#### ⚙️ System
- `settings` - System settings & configuration
- `cms_pages` - CMS content pages
- `cart_items` - Shopping cart

---

## 🎨 Default Data Included

### **Admin Users**
| Email | Password | Role |
|-------|----------|------|
| admin1@dorve.co | Dorve889 | admin |
| admin2@dorve.co | Admin889 | admin |

### **Categories (8 Total)**
- T-Shirts
- Hoodies
- Jeans
- Dresses
- Jackets
- Accessories
- Shoes
- Bags

### **Shipping Methods (4 Total)**
| Method | Cost | Delivery Time |
|--------|------|---------------|
| Regular Shipping | Rp 15,000 | 3-5 days |
| Express Shipping | Rp 25,000 | 1-2 days |
| Free Shipping | Rp 0 | 5-7 days |
| Same Day Delivery | Rp 50,000 | Same day |

### **Vouchers (8 Total)**
| Code | Type | Value | Min Purchase | Tier |
|------|------|-------|--------------|------|
| WELCOME10 | Percentage | 10% | Rp 100,000 | All |
| NEWUSER50K | Fixed | Rp 50,000 | Rp 500,000 | All |
| FREESHIP50K | Free Shipping | - | Rp 50,000 | All |
| SILVER20 | Percentage | 20% | Rp 200,000 | Silver |
| GOLD25 | Percentage | 25% | Rp 300,000 | Gold |
| PLATINUM30 | Percentage | 30% | Rp 500,000 | Platinum |
| VVIP40 | Percentage | 40% | Rp 1,000,000 | VVIP |
| VVIPFREE | Free Shipping | - | Rp 0 | VVIP |

---

## 💎 Customer Tier System

| Tier | Total Topup Required | Benefits |
|------|---------------------|----------|
| 🟤 **Bronze** | Under Rp 1,000,000 | Basic access |
| ⚪ **Silver** | Rp 1,000,000 - 2,999,999 | 20% vouchers |
| 🟡 **Gold** | Rp 3,000,000 - 9,999,999 | 25% vouchers |
| ⚪ **Platinum** | Rp 10,000,000 - 19,999,999 | 30% vouchers |
| 💜 **VVIP** | Rp 20,000,000+ | 40% vouchers + Free shipping |

**Automatic Upgrade:** Tier otomatis naik berdasarkan total topup wallet

---

## 🎁 Referral System

### **How It Works:**
1. User A mendapat referral code (auto-generated)
2. User A share code ke User B
3. User B register dengan referral code
4. User B topup wallet (minimum Rp 100,000)
5. User A dapat commission 5% dari topup pertama User B

### **Settings (Adjustable):**
- **Commission Percent:** Default 5% (bisa diubah admin)
- **Min Topup:** Rp 100,000
- **Reward:** Auto-credit ke wallet referrer

### **Anti-Manipulation:**
✅ Commission hanya untuk topup pertama  
✅ Auto-calculated (no manual editing)  
✅ Full audit trail  
✅ Minimum topup requirement  

---

## 🔧 System Features

### **Admin Panel (/admin/)**
- ✅ Dashboard
- ✅ Product Management
- ✅ Category Management
- ✅ Order Management & Tracking
- ✅ User Management
- ✅ Voucher Management
- ✅ Referral System Settings
- ✅ Wallet Topup Approval
- ✅ System Settings
- ✅ Shipping Settings

### **Member Area (/member/)**
- ✅ Dashboard
- ✅ Order History
- ✅ Order Tracking
- ✅ Wallet Management
- ✅ Referral Dashboard
- ✅ Address Management
- ✅ Profile Settings
- ✅ Product Reviews

### **Public Pages (/pages/)**
- ✅ Homepage
- ✅ All Products
- ✅ New Collection
- ✅ Product Detail
- ✅ Shopping Cart
- ✅ Checkout
- ✅ FAQ, Terms, Privacy Policy

---

## 🔐 Security Features

- ✅ Password hashing (bcrypt)
- ✅ SQL injection protection (PDO prepared statements)
- ✅ Foreign key constraints
- ✅ Role-based access control
- ✅ Session management
- ✅ Input validation

---

## 📱 Responsive & Mobile-Ready

- ✅ Mobile-friendly design
- ✅ Touch-optimized interface
- ✅ Fast loading
- ✅ Modern UI/UX

---

## 🔄 Database Maintenance

### **Stored Procedures:**

1. **update_user_tier(user_id)**
   - Auto-upgrade customer tier based on total topup
   - Called after each completed topup

2. **process_referral_reward(topup_id)**
   - Calculate & credit referral commission
   - Called after first topup completion

### **Triggers:**

1. **after_order_status_update**
   - Auto-create order timeline entry
   - Triggered when order status changes

2. **after_variant_stock_update**
   - Auto-update product total stock
   - Triggered when variant stock changes

3. **after_variant_insert**
   - Auto-calculate product stock
   - Triggered when new variant added

### **Views:**

1. **order_tracking_view**
   - Easy order tracking queries
   - Combines orders + users + timeline

2. **product_inventory_view**
   - Quick inventory check
   - Combines products + variants + categories

---

## 🚨 Troubleshooting

### **Problem: "Connection failed"**
**Solution:** 
- Check database credentials di `config.php`
- Pastikan MySQL service running
- Verify database name exists

### **Problem: "Permission denied"**
**Solution:**
- Check file permissions (644 for PHP, 755 for directories)
- Verify database user has proper privileges
- Grant ALL privileges to dorve_dorve user

### **Problem: "Syntax error in SQL"**
**Solution:**
- Ensure MySQL version 5.7+ or MariaDB 10.2+
- Check if stored procedures are supported
- Try manual import via phpMyAdmin

### **Problem: "Timeout during import"**
**Solution:**
- Increase PHP max_execution_time
- Use command line import:
  ```bash
  mysql -u dorve_dorve -p dorve_dorve < COMPLETE-DATABASE-RESTORE.sql
  ```

---

## 📞 Support

**Database Configuration:**
- Host: localhost
- Database: dorve_dorve
- User: dorve_dorve
- Charset: utf8mb4

**Default Admin Credentials:**
- Email: admin1@dorve.co
- Password: Dorve889

**Files Created:**
1. `COMPLETE-DATABASE-RESTORE.sql` - Main SQL file
2. `restore-database.php` - Auto-import script
3. `DATABASE-RESTORATION-GUIDE.md` - This guide

---

## ✨ Next Steps

1. ✅ **Restore database** (using auto-import or manual)
2. ✅ **Login admin panel** (`/admin/login.php`)
3. ✅ **Add products** (`/admin/products/`)
4. ✅ **Configure settings** (`/admin/settings/`)
5. ✅ **Test features**:
   - Register new customer
   - Add products to cart
   - Checkout with voucher
   - Test referral system
   - Test wallet topup
6. ✅ **Customize design** (optional)
7. ✅ **Launch to production** 🚀

---

## 🎉 All Features Ready!

✅ **Admin Panel** - Fully functional  
✅ **Member Area** - Complete features  
✅ **Product Management** - With variants & stock  
✅ **Order System** - With tracking  
✅ **Wallet System** - Topup & payment  
✅ **Referral System** - Auto-commission  
✅ **Tier System** - Auto-upgrade  
✅ **Voucher System** - Tier-based  
✅ **CMS Pages** - Ready to edit  
✅ **Settings** - Fully configurable  

---

**Database Version:** 4.0 MASTER  
**Last Updated:** 2025-12-05  
**Status:** ✅ Production Ready

---

🚀 **Selamat! Database Dorve House siap digunakan!**
