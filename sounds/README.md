# Sound Files untuk Admin Notifications

## Required Sound Files:

### 1. new-order.mp3
**Purpose:** Notifikasi untuk order baru yang sudah PAID tapi belum di-print

**Requirements:**
- Duration: 1-2 seconds
- Format: MP3
- Volume: Medium (not too loud)
- Tone: Pleasant, professional (e.g., cash register "cha-ching" or bell)
- Loop: Will be repeated every 10 seconds until admin responds

**When to play:**
- New order dengan status PAID
- Order belum di-print (printed_at IS NULL)
- Plays automatically in background
- Stops when admin clicks anywhere or prints order

**Download Free Sound:**
- https://freesound.org/search/?q=cash+register
- https://freesound.org/search/?q=notification+bell
- https://pixabay.com/sound-effects/search/cash-register/

---

### 2. new-deposit.mp3
**Purpose:** Notifikasi untuk deposit/topup baru yang pending confirmation

**Requirements:**
- Duration: 1-2 seconds
- Format: MP3
- Volume: Medium
- Tone: Different from order sound, slightly higher pitch (e.g., coin drop or success chime)
- Loop: Will be repeated every 10 seconds

**When to play:**
- New deposit dengan status PENDING
- Payment method: Bank Transfer (manual confirmation needed)
- Plays automatically in /admin/deposits/index.php
- Stops when admin approves/rejects or clicks anywhere

**Download Free Sound:**
- https://freesound.org/search/?q=coin+drop
- https://freesound.org/search/?q=success+chime
- https://pixabay.com/sound-effects/search/coin/

---

## Sound File Locations:

```
/app/sounds/
├── new-order.mp3       ← Place order sound here
├── new-deposit.mp3     ← Place deposit sound here
└── README.md           ← This file
```

---

## How to Add Sound Files:

### Option 1: Upload via FTP/File Manager
1. Download sound files dari website di atas
2. Rename sesuai: `new-order.mp3` dan `new-deposit.mp3`
3. Upload ke folder `/app/sounds/`
4. Make sure file permissions allow reading (chmod 644)

### Option 2: Use Placeholder (Testing)
Jika belum ada sound files, browser akan show console warning tapi app tetap jalan normal.

### Option 3: Generate with Text-to-Speech (Quick & Easy)
```bash
# Using gtts (Google Text-to-Speech) - requires Python
pip install gtts

# Generate order sound
echo "New order received" | gtts-cli --lang en - -o new-order.mp3

# Generate deposit sound  
echo "New deposit pending" | gtts-cli --lang en - -o new-deposit.mp3
```

---

## Technical Details:

**HTML Audio Implementation:**
```html
<audio id="newOrderSound" preload="auto">
    <source src="/sounds/new-order.mp3" type="audio/mpeg">
</audio>
```

**JavaScript Play:**
```javascript
const sound = document.getElementById('newOrderSound');
sound.play().catch(e => console.log('Sound play failed:', e));
```

**Auto-repeat every 10 seconds:**
```javascript
soundInterval = setInterval(playOrderSound, 10000);
```

**Stop on user interaction:**
```javascript
document.addEventListener('click', () => {
    clearInterval(soundInterval);
});
```

---

## Browser Compatibility:

- ✅ Chrome: Full support
- ✅ Firefox: Full support
- ✅ Safari: Full support (may require user interaction first)
- ✅ Edge: Full support

**Note:** Some browsers require user interaction before playing audio (autoplay policy).
The sound will play AFTER first click on the page.

---

## Troubleshooting:

**Sound not playing?**
1. Check file exists: `/app/sounds/new-order.mp3`
2. Check browser console for errors (F12)
3. Check file permissions (should be readable)
4. Try clicking anywhere on page first (browser autoplay policy)
5. Check volume not muted

**Sound too loud/quiet?**
Edit the audio element:
```javascript
sound.volume = 0.5; // 50% volume (0.0 to 1.0)
```

**Want different sound?**
Just replace the MP3 file with same filename, no code changes needed!
