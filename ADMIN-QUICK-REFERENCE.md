# 👨‍💼 Admin Quick Reference Guide

## 🔐 Admin Login Credentials

### **Admin 1**
- **Email:** admin1@dorve.co
- **Password:** Dorve889
- **URL:** https://dorve.co/admin/login.php

### **Admin 2**
- **Email:** admin2@dorve.co  
- **Password:** Admin889
- **URL:** https://dorve.co/admin/login.php

---

## 📊 Admin Panel Overview

### **Dashboard** (`/admin/index.php`)
- Total Users
- Total Orders
- Total Revenue
- Recent Orders
- Low Stock Alerts

---

## 🛍️ Product Management

### **Add New Product** (`/admin/products/create.php`)
```
Required Fields:
- Product Name
- Price
- Category
- Description
- Image (main)
- Stock (or variants)

Optional:
- Discount Price
- Gender (men/women/unisex)
- Is New Collection
- Is Featured
- SEO Meta tags
```

### **Product Variants**
```
Each variant needs:
- Size (S, M, L, XL, XXL)
- Color (Red, Blue, Black, etc)
- Stock quantity
- SKU (optional)
- Price adjustment (optional)

Example:
Product: T-Shirt Basic
- Variant 1: Size M, Color Black, Stock 50
- Variant 2: Size L, Color Black, Stock 30
- Variant 3: Size M, Color White, Stock 45
```

### **Stock Management**
- Total product stock = SUM of all variant stocks
- Auto-updated via triggers
- Low stock alert when < 10 units

---

## 📦 Order Management

### **Order Status Flow**
```
pending → processing → shipping → delivered
         ↓
    cancelled (anytime)
```

### **Order Actions**
1. **View Order:** See full details + timeline
2. **Update Status:** Change to next status
3. **Add Tracking:** Input courier + tracking number
4. **Cancel Order:** Select reason + auto-refund to wallet
5. **Print Invoice:** Generate PDF invoice

### **Courier Options**
- JNE
- JNT (J&T)
- SiCepat
- AnterAja
- Ninja Express
- GoSend
- GrabExpress

---

## 💰 Wallet & Topup Management

### **Approve Topup** (`/admin/deposits/`)
```
Steps:
1. Customer request topup
2. Customer transfer ke rekening toko
3. Admin verify payment
4. Admin approve topup
5. Balance auto-credited
6. Tier auto-upgraded if threshold reached
7. Referral commission auto-paid (if first topup)
```

### **Topup Status**
- **Pending:** Waiting approval
- **Completed:** Approved & credited
- **Failed:** Rejected by admin
- **Cancelled:** Cancelled by customer

---

## 🎁 Voucher Management

### **Create Voucher** (`/admin/vouchers/create.php`)

#### **Types:**
1. **Percentage** (e.g., 10%, 20%, 50%)
2. **Fixed Amount** (e.g., Rp 50,000)
3. **Free Shipping**

#### **Category:**
- **Discount** (applies to subtotal)
- **Free Shipping** (removes shipping cost)

#### **Target Tier:**
- All (everyone can use)
- Bronze, Silver, Gold, Platinum, VVIP (specific tiers)

#### **Example Vouchers:**
```
Code: WELCOME10
Type: Percentage
Value: 10%
Min Purchase: Rp 100,000
Target Tier: All
Valid: 1 year

Code: VVIP40
Type: Percentage
Value: 40%
Min Purchase: Rp 1,000,000
Target Tier: VVIP only
Valid: 1 year

Code: FREESHIP
Type: Free Shipping
Value: 0
Min Purchase: Rp 0
Target Tier: VVIP
Valid: 1 year
```

---

## 👥 User Management

### **User Roles**
- **Customer:** Normal user (can shop)
- **Admin:** Full access to admin panel

### **User Information**
- Name, Email, Phone
- Wallet Balance
- Referral Code (auto-generated)
- Tier (Bronze → VVIP)
- Total Topup Amount
- Total Referrals
- Registration Date

### **Actions**
- View user details
- Edit user info
- Add/Remove wallet balance
- View order history
- View referral history
- Ban/Unban user (optional)

---

## 🎯 Referral System Settings

### **Configure** (`/admin/referrals/settings.php`)

#### **Default Settings:**
```
Commission Percent: 5%
Min Topup for Reward: Rp 100,000
Referral Code Prefix: DRV
System Status: Active
```

#### **How to Change Commission:**
```sql
UPDATE referral_settings 
SET setting_value = '10.00' 
WHERE setting_key = 'commission_percent';
```

#### **Commission Examples:**
- 5% of Rp 1,000,000 = Rp 50,000
- 10% of Rp 1,000,000 = Rp 100,000
- 15% of Rp 500,000 = Rp 75,000

---

## 💎 Customer Tier System

### **Tier Thresholds**
| Tier | Total Topup | Auto Benefits |
|------|-------------|---------------|
| 🟤 Bronze | < Rp 1M | Basic |
| ⚪ Silver | Rp 1M - 2.9M | 20% vouchers |
| 🟡 Gold | Rp 3M - 9.9M | 25% vouchers |
| ⚪ Platinum | Rp 10M - 19.9M | 30% vouchers |
| 💜 VVIP | ≥ Rp 20M | 40% vouchers + Free ship |

### **Automatic Upgrade**
- Tiers upgrade automatically after topup approval
- Based on total lifetime topup amount
- History logged in `tier_upgrades` table

### **Manual Tier Upgrade (SQL)**
```sql
-- Upgrade user ID 10 to Gold
UPDATE users 
SET tier = 'gold', total_topup_amount = 3000000 
WHERE id = 10;

-- Insert upgrade history
INSERT INTO tier_upgrades (user_id, from_tier, to_tier, total_topup_at_upgrade)
VALUES (10, 'silver', 'gold', 3000000);
```

---

## 📧 Email Verification (Optional)

If email verification enabled:
- User must verify email after registration
- Verification link sent to email
- User can't shop until verified
- Admin can manually verify users

---

## 🚚 Shipping Management

### **Shipping Methods** (`/admin/shipping/`)
```
Add/Edit Shipping Methods:
- Method Name (e.g., "JNE Regular")
- Description
- Base Cost
- Estimated Delivery Days
- Active Status
- Sort Order
```

### **Shipping Cost Calculation**
```
Option 1: Flat Rate
- Fixed cost per order
- Easy to manage

Option 2: Weight-Based (requires weight per product)
- Calculate based on total weight
- More accurate

Option 3: API Integration (BitShip, Shipper)
- Real-time cost calculation
- Multiple courier options
- Needs API key
```

---

## ⚙️ System Settings

### **General Settings** (`/admin/settings/`)
```
Store Information:
- Store Name: Dorve House
- Email: info@dorve.co
- Phone: 6281377378859
- Address: Jakarta, Indonesia

Currency:
- Currency: IDR
- Symbol: Rp

Homepage:
- Marquee Text (scrolling banner)
- Marquee Enabled (on/off)
- Promotion Banner Image
- Promotion Banner Link

WhatsApp:
- Number for customer service
- Click to chat integration
```

### **Payment Settings**
```
Midtrans (Optional):
- Server Key
- Client Key
- Environment (sandbox/production)
- Status (enabled/disabled)

Wallet Payment:
- Always available
- Default payment method
```

---

## 📱 CMS Pages Management

### **Edit Pages** (`/admin/pages/`)
```
Available Pages:
- About Us
- Privacy Policy
- Terms & Conditions
- Shipping Policy
- FAQ

Fields:
- Title
- Slug (URL)
- Content (rich text editor)
- Meta Title (SEO)
- Meta Description (SEO)
- Active Status
```

---

## 📊 Reports & Analytics

### **Sales Report**
- Total revenue by date range
- Best selling products
- Revenue by category
- Order status distribution

### **Customer Report**
- New registrations
- Active customers
- Customer by tier
- Top spenders

### **Product Report**
- Stock levels
- Low stock alerts
- Best sellers
- Slow moving products

### **Referral Report**
- Total referrals
- Commission paid
- Top referrers
- Pending rewards

---

## 🔧 Maintenance Tasks

### **Daily Tasks**
✅ Check pending orders  
✅ Approve pending topups  
✅ Reply to customer messages  
✅ Check low stock products  

### **Weekly Tasks**
✅ Review sales reports  
✅ Update product info  
✅ Check voucher performance  
✅ Review customer feedback  

### **Monthly Tasks**
✅ Generate monthly report  
✅ Plan promotions  
✅ Update tier benefits  
✅ Analyze best sellers  

---

## 🚨 Common Issues & Solutions

### **Issue: Order payment failed**
**Solution:**
- Check customer wallet balance
- Verify order amount
- Check if voucher is valid
- Review error logs

### **Issue: Stock not updating**
**Solution:**
- Check product variants
- Verify triggers are working
- Manual sync:
  ```sql
  UPDATE products p
  SET stock = (
      SELECT SUM(stock) 
      FROM product_variants 
      WHERE product_id = p.id
  );
  ```

### **Issue: Referral commission not paid**
**Solution:**
- Check if first topup
- Verify topup amount ≥ minimum
- Check referral_settings active
- Run procedure manually:
  ```sql
  CALL process_referral_reward(topup_id);
  ```

### **Issue: Tier not upgrading**
**Solution:**
- Check total_topup_amount
- Verify completed topups only counted
- Manual trigger:
  ```sql
  CALL update_user_tier(user_id);
  ```

---

## 🎯 Quick SQL Queries

### **Check Total Orders Today**
```sql
SELECT COUNT(*) as total_orders, SUM(total_amount) as revenue
FROM orders 
WHERE DATE(created_at) = CURDATE();
```

### **Find Low Stock Products**
```sql
SELECT id, name, stock 
FROM products 
WHERE stock < 10 AND is_active = 1
ORDER BY stock ASC;
```

### **Top 10 Customers by Spending**
```sql
SELECT u.name, u.email, SUM(o.total_amount) as total_spent
FROM users u
JOIN orders o ON u.id = o.user_id
WHERE o.status != 'cancelled'
GROUP BY u.id
ORDER BY total_spent DESC
LIMIT 10;
```

### **Pending Topups**
```sql
SELECT t.*, u.name, u.email
FROM topups t
JOIN users u ON t.user_id = u.id
WHERE t.status = 'pending'
ORDER BY t.created_at DESC;
```

### **Active Vouchers**
```sql
SELECT code, type, value, min_purchase, target_tier, used_count
FROM vouchers
WHERE is_active = 1 
  AND valid_until >= CURDATE()
ORDER BY created_at DESC;
```

---

## 🎨 Customization Tips

### **Change Logo**
```
Upload new logo to: /public/images/logo.png
Recommended size: 200x60px (transparent background)
```

### **Change Colors**
```
Edit: /public/css/style.css
Main colors:
- Primary: #667eea
- Secondary: #764ba2
- Success: #28a745
- Danger: #dc3545
```

### **Add Custom Pages**
```
1. Create new PHP file in /pages/
2. Use existing header/footer
3. Add to navigation menu
4. Update sitemap
```

---

## 📞 Support Contacts

**Technical Issues:**
- Check error logs: `/var/log/apache2/error.log`
- PHP errors: `/var/log/php_errors.log`
- Database: Check phpMyAdmin

**Database Access:**
- phpMyAdmin: https://dorve.co/phpmyadmin
- Host: localhost
- User: dorve_dorve
- Database: dorve_dorve

---

## ✅ Admin Checklist

**After Database Restoration:**
- [ ] Login to admin panel
- [ ] Add product categories
- [ ] Add products with variants
- [ ] Test order flow
- [ ] Configure vouchers
- [ ] Set referral commission rate
- [ ] Update store settings
- [ ] Test wallet topup
- [ ] Test referral system
- [ ] Review all pages
- [ ] Set shipping methods
- [ ] Test checkout process
- [ ] Launch! 🚀

---

**Quick Access URLs:**
- Admin Login: `/admin/login.php`
- Dashboard: `/admin/index.php`
- Products: `/admin/products/`
- Orders: `/admin/orders/`
- Users: `/admin/users/`
- Vouchers: `/admin/vouchers/`
- Settings: `/admin/settings/`

---

🎉 **Semua siap! Selamat mengelola Dorve House!**
