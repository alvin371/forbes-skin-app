# ScrapingBot Social Media API — README (TikTok, Instagram, Threads)

This README explains how to use **ScrapingBot Social Media API** to scrape:

- TikTok **Profile (User)**
- TikTok **Hashtag feed** (videos/posts from a hashtag)
- Instagram **Profile**
- Instagram **Post**
- Threads **Profile**
- Threads **Post**

> The Social Media API works in **2 steps**:  
> **(1) POST** to start scraping → get a `responseId`  
> **(2) GET** using `responseId` until the result is ready

---

## Base URLs

- Start scrape (POST):  
  `http://api.scraping-bot.io/scrape/data-scraper`

- Fetch result (GET):  
  `http://api.scraping-bot.io/scrape/data-scraper-response`

---

## Authentication

Uses **Basic Auth**:

- Username: your ScrapingBot username
- Password: your API Key

### Curl format
```bash
-u "YOUR_USERNAME:YOUR_API_KEY"
```

---

## Two-step Flow (Important)

### 1) Start scrape (POST)
You send JSON with:
- `scraper` (required)
- plus **required params for that scraper** (url/account/hashtag/etc.)

Response returns:
```json
{
  "responseId": "xxxx"
}
```

### 2) Poll result (GET)
You request:
- `scraper` (required, must match POST)
- `responseId` (required)

Result can be:
- `status: "pending"` → not ready, retry later
- `status: "success"` → data returned
- `status: "error"` → failed (credits refunded per docs)

---

# Supported Scrapers (This README)

## 1) TikTok Profile (User)
### Scraper
- `tiktokProfile`

### Required params
- `url`

### Optional params
- `max_video_count` (int, default `30`) — number of videos to extract

---

## 2) TikTok Hashtag (Videos/Posts Feed)
### Scraper
- `tiktokHashtag`

### Required params
- `hashtag`

### Optional params
- `max_video_count` (int, default `30`)

---

## 3) Instagram Profile
### Scraper
- `instagramProfile`

### Required params
- `account`

### Optional params
- `posts_number` (String, default `"12"`) — number of posts to extract

---

## 4) Instagram Post
### Scraper
- `instagramPost`

### Required params
- `url` (Instagram post URL)

---

## 5) Threads Profile
### Scraper
- `threadsProfile`

### Required params
- `account`

---

## 6) Threads Post
### Scraper
- `threadsPost`

### Required params
- `url` (Threads post URL)

---

## Polling Strategy (Recommended)

Social media scraping can take time. The docs suggest polling every **~5 seconds** (or more), and not spamming.

Pseudo:
1. POST → get `responseId`
2. Repeat:
   - wait 5s
   - GET result
   - stop if status != "pending"

---

