-- Alora shop database (SQLite). Money is stored in cents (integers).
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS categories (
  id          INTEGER PRIMARY KEY,
  wp_id       INTEGER UNIQUE,
  slug        TEXT NOT NULL UNIQUE,
  name        TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  image       TEXT,                       -- media path, e.g. c/accessories
  sort        INTEGER NOT NULL DEFAULT 0,
  active      INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS products (
  id            INTEGER PRIMARY KEY,
  wp_id         INTEGER UNIQUE,
  slug          TEXT NOT NULL UNIQUE,
  name          TEXT NOT NULL,
  sku           TEXT,
  short_desc    TEXT NOT NULL DEFAULT '',
  description   TEXT NOT NULL DEFAULT '',
  price_cents   INTEGER NOT NULL,           -- what the customer pays now
  regular_cents INTEGER NOT NULL,           -- before any sale
  stock_qty     INTEGER,                    -- NULL = not tracked (always available)
  is_bundle     INTEGER NOT NULL DEFAULT 0,
  featured      INTEGER NOT NULL DEFAULT 0,
  active        INTEGER NOT NULL DEFAULT 1,
  created_at    TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS product_categories (
  product_id  INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  PRIMARY KEY (product_id, category_id)
);

CREATE TABLE IF NOT EXISTS product_images (
  id          INTEGER PRIMARY KEY,
  product_id  INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  wp_media_id INTEGER,
  original    TEXT NOT NULL,                -- file in storage/originals
  path        TEXT NOT NULL,                -- public base name in /media (without size + .webp)
  alt         TEXT NOT NULL DEFAULT '',
  width       INTEGER,
  height      INTEGER,
  sort        INTEGER NOT NULL DEFAULT 0
);

-- A bundle product takes stock from each item in it.
CREATE TABLE IF NOT EXISTS bundle_items (
  bundle_id  INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  qty        INTEGER NOT NULL DEFAULT 1,
  PRIMARY KEY (bundle_id, product_id)
);

CREATE TABLE IF NOT EXISTS customers (
  id            INTEGER PRIMARY KEY,
  email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
  password_hash TEXT,
  name          TEXT NOT NULL DEFAULT '',
  phone         TEXT NOT NULL DEFAULT '',
  address_json  TEXT,
  failed_logins INTEGER NOT NULL DEFAULT 0,
  locked_until  TEXT,
  created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS staff (
  id            INTEGER PRIMARY KEY,
  email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
  password_hash TEXT NOT NULL,
  name          TEXT NOT NULL,
  role          TEXT NOT NULL DEFAULT 'staff' CHECK (role IN ('owner','staff')),
  failed_logins INTEGER NOT NULL DEFAULT 0,
  locked_until  TEXT,
  created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS shipping_methods (
  id          INTEGER PRIMARY KEY,
  name        TEXT NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  price_cents INTEGER NOT NULL,
  needs_address INTEGER NOT NULL DEFAULT 1,
  active      INTEGER NOT NULL DEFAULT 1,
  sort        INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS coupons (
  id              INTEGER PRIMARY KEY,
  code            TEXT NOT NULL UNIQUE COLLATE NOCASE,
  type            TEXT NOT NULL CHECK (type IN ('percent','fixed')),
  amount          INTEGER NOT NULL,          -- percent (1-100) or cents
  min_spend_cents INTEGER NOT NULL DEFAULT 0,
  expires_at      TEXT,
  usage_limit     INTEGER,
  used_count      INTEGER NOT NULL DEFAULT 0,
  active          INTEGER NOT NULL DEFAULT 1,
  created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS orders (
  id              INTEGER PRIMARY KEY,
  number          TEXT NOT NULL UNIQUE,      -- shown to customers, e.g. AL-10001
  customer_id     INTEGER REFERENCES customers(id) ON DELETE SET NULL,
  email           TEXT NOT NULL,
  name            TEXT NOT NULL,
  phone           TEXT NOT NULL DEFAULT '',
  address_json    TEXT,
  shipping_name   TEXT NOT NULL,
  shipping_cents  INTEGER NOT NULL,
  subtotal_cents  INTEGER NOT NULL,
  discount_cents  INTEGER NOT NULL DEFAULT 0,
  total_cents     INTEGER NOT NULL,
  coupon_code     TEXT,
  status          TEXT NOT NULL DEFAULT 'pending'
                  CHECK (status IN ('pending','paid','packed','shipped','completed','cancelled')),
  payfast_id      TEXT,
  tracking        TEXT,
  customer_note   TEXT NOT NULL DEFAULT '',
  access_token    TEXT NOT NULL,             -- lets a guest view their own order page
  created_at      TEXT NOT NULL DEFAULT (datetime('now')),
  paid_at         TEXT,
  updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS order_items (
  id          INTEGER PRIMARY KEY,
  order_id    INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id  INTEGER REFERENCES products(id) ON DELETE SET NULL,
  name        TEXT NOT NULL,
  unit_cents  INTEGER NOT NULL,
  qty         INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS order_events (
  id         INTEGER PRIMARY KEY,
  order_id   INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  status     TEXT NOT NULL,
  note       TEXT NOT NULL DEFAULT '',
  by_staff   INTEGER REFERENCES staff(id) ON DELETE SET NULL,
  emailed    INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS password_resets (
  token_hash TEXT PRIMARY KEY,
  who        TEXT NOT NULL CHECK (who IN ('customer','staff')),
  user_id    INTEGER NOT NULL,
  expires_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
  key   TEXT PRIMARY KEY,
  value TEXT NOT NULL
);

-- Old WordPress URLs that need a 301 to a new address.
CREATE TABLE IF NOT EXISTS redirects (
  old_path TEXT PRIMARY KEY,
  new_path TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS newsletter (
  email      TEXT PRIMARY KEY COLLATE NOCASE,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Privacy-friendly visit counts: one row per day and path, no visitor data.
CREATE TABLE IF NOT EXISTS page_views (
  day   TEXT NOT NULL,
  path  TEXT NOT NULL,
  views INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (day, path)
);

CREATE INDEX IF NOT EXISTS idx_products_active ON products(active);
CREATE INDEX IF NOT EXISTS idx_images_product ON product_images(product_id, sort);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders(customer_id);
