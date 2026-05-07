create table users (
    id int auto_increment primary key,
    name varchar(255) not null,
    email varchar(255) not null,
    password varchar(255) not null,
    role varchar(10) not null default 'user', -- user | staff | admin
    last_seen timestamp default current_timestamp,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp
);

create table accounts (
    id int auto_increment primary key,
    user_id int not null,
    account_number varchar(20) not null,
    balance decimal(18, 2) not null default 0.00,
    status varchar(10) not null default 'active', -- active | inactive
    currency varchar(3) not null default 'LAK',
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp,
    foreign key (user_id) references users(id)
);
create table transactions (
    id int auto_increment primary key,
    account_id int not null,
    type varchar(10) not null, -- deposit | withdrawal | transfer
    amount decimal(18, 2) not null,
    description text,
    reference varchar(255),
    created_at timestamp default current_timestamp,
    user_id int not null references users(id),
    foreign key (account_id) references accounts(id)
);

-- ຕາຕະລາງບັນທຶກການເຂົ້າຊົມທົ່ວໄປ
create table visitor_logs (
    id int auto_increment primary key,
    ip_address varchar(45),
    user_agent text,
    page_url varchar(500),
    referer varchar(500),
    session_id varchar(100),
    user_id int null,
    created_at timestamp default current_timestamp,
    foreign key (user_id) references users(id) on delete set null
);

-- ຕາຕະລາງບັນທຶກການ Login/Logout
create table auth_logs (
    id int auto_increment primary key,
    user_id int null,
    email varchar(255),
    action varchar(20) not null, -- login_success | login_failed | logout
    ip_address varchar(45),
    user_agent text,
    status_message varchar(255),
    created_at timestamp default current_timestamp,
    foreign key (user_id) references users(id) on delete set null
);

-- Sample data: password for all sample users is "password"
insert into users (id, name, email, password, role, last_seen, created_at, updated_at) values
(1, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-05-06 08:10:00', '2026-05-01 09:00:00', '2026-05-01 09:00:00'),
(2, 'Staff User', 'staff@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '2026-05-06 08:20:00', '2026-05-01 09:10:00', '2026-05-01 09:10:00'),
(3, 'Khamla Phommachanh', 'khamla@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2026-05-05 17:45:00', '2026-05-01 09:20:00', '2026-05-01 09:20:00'),
(4, 'Noy Vongsai', 'noy@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2026-05-04 14:30:00', '2026-05-01 09:30:00', '2026-05-01 09:30:00'),
(5, 'Dao Sihalath', 'dao@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2026-05-03 11:15:00', '2026-05-01 09:40:00', '2026-05-01 09:40:00');

insert into accounts (id, user_id, account_number, balance, status, currency, created_at, updated_at) values
(1, 1, 'LAK1000000001', 25000000.00, 'active', 'LAK', '2026-05-01 10:00:00', '2026-05-01 10:00:00'),
(2, 2, 'LAK1000000002', 12500000.00, 'active', 'LAK', '2026-05-01 10:10:00', '2026-05-01 10:10:00'),
(3, 3, 'LAK1000000003', 3500000.00, 'active', 'LAK', '2026-05-01 10:20:00', '2026-05-01 10:20:00'),
(4, 4, 'LAK1000000004', 7600000.00, 'active', 'LAK', '2026-05-01 10:30:00', '2026-05-01 10:30:00'),
(5, 5, 'LAK1000000005', 0.00, 'inactive', 'LAK', '2026-05-01 10:40:00', '2026-05-01 10:40:00');

insert into transactions (id, account_id, type, amount, description, reference, created_at, user_id) values
(1, 1, 'deposit', 5000000.00, 'Initial cash deposit', 'TXN-20260501-0001', '2026-05-01 11:00:00', 1),
(2, 2, 'withdrawal', 1500000.00, 'ATM withdrawal', 'TXN-20260501-0002', '2026-05-01 11:15:00', 2),
(3, 3, 'deposit', 300000.00, 'Counter deposit', 'TXN-20260501-0003', '2026-05-01 11:30:00', 3),
(4, 4, 'transfer', 800000.00, 'Transfer to savings account', 'TXN-20260501-0004', '2026-05-01 11:45:00', 4),
(5, 5, 'deposit', 1000000.00, 'Account opening deposit', 'TXN-20260501-0005', '2026-05-01 12:00:00', 5);

insert into visitor_logs (id, ip_address, user_agent, page_url, referer, session_id, user_id, created_at) values
(1, '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '/src/login/index.php', '/', 'sess_202605060001', null, '2026-05-06 08:00:00'),
(2, '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '/src/dashboard/index.php', '/src/login/index.php', 'sess_202605060002', 1, '2026-05-06 08:12:00'),
(3, '192.168.1.103', 'Mozilla/5.0 (X11; Linux x86_64)', '/src/account/index.php', '/src/dashboard/index.php', 'sess_202605060003', 2, '2026-05-06 08:22:00'),
(4, '192.168.1.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', '/src/profile/index.php', '/src/dashboard/index.php', 'sess_202605060004', 3, '2026-05-06 08:32:00'),
(5, '192.168.1.105', 'Mozilla/5.0 (Android 14; Mobile)', '/src/users/index.php', '/src/dashboard/index.php', 'sess_202605060005', 1, '2026-05-06 08:42:00');

insert into auth_logs (id, user_id, email, action, ip_address, user_agent, status_message, created_at) values
(1, 1, 'admin@example.com', 'login_success', '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'Login successful', '2026-05-06 08:10:00'),
(2, 2, 'staff@example.com', 'login_success', '192.168.1.103', 'Mozilla/5.0 (X11; Linux x86_64)', 'Login successful', '2026-05-06 08:20:00'),
(3, null, 'unknown@example.com', 'login_failed', '192.168.1.150', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Account not found', '2026-05-06 08:25:00'),
(4, 3, 'khamla@example.com', 'logout', '192.168.1.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)', 'Logout successful', '2026-05-06 08:35:00'),
(5, 1, 'admin@example.com', 'logout', '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'Logout successful', '2026-05-06 08:50:00');


-- สร้างตารางหมวดหมู่ (ใช้ ID เป็นตัวเลข)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(50) UNIQUE NOT NULL, -- เก็บชื่อเรียกภาษาอังกฤษเดิมไว้ (เช่น 'coffee')
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(7)
);

-- สร้างตารางสินค้า (เชื่อมโยงด้วย category_id ที่เป็นตัวเลข)
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    image_url VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO categories (id, slug, name, icon, color) VALUES
(1, 'all', 'All Items', 'fa-border-all', '#F59E0B'),
(2, 'coffee', 'Coffee & Drinks', 'fa-mug-hot', '#D97706'),
(3, 'bakery', 'Pastries', 'fa-cookie-bite', '#EF4444'),
(4, 'sandwiches', 'Sandwiches', 'fa-bread-slice', '#059669'),
(5, 'salads', 'Salads & Bowls', 'fa-leaf', '#10B981'),
(6, 'desserts', 'Desserts', 'fa-ice-cream', '#EC4899'),
(7, 'snacks', 'Quick Snacks', 'fa-bolt', '#8B5CF6');

INSERT INTO products (id, name, price, category_id, image_url) VALUES
(1, 'Espresso', 3.50, 2, 'https://picsum.photos/seed/esp01/200/200'),
(2, 'Americano', 4.00, 2, 'https://picsum.photos/seed/ame02/200/200'),
(3, 'Cappuccino', 4.50, 2, 'https://picsum.photos/seed/cap03/200/200'),
(4, 'Latte', 5.00, 2, 'https://picsum.photos/seed/lat04/200/200'),
(5, 'Mocha', 5.50, 2, 'https://picsum.photos/seed/moc05/200/200'),
(6, 'Iced Coffee', 4.50, 2, 'https://picsum.photos/seed/icd06/200/200'),
(7, 'Hot Chocolate', 4.50, 2, 'https://picsum.photos/seed/hch07/200/200'),
(8, 'Fresh OJ', 5.00, 2, 'https://picsum.photos/seed/foj08/200/200'),
(9, 'Croissant', 3.00, 3, 'https://picsum.photos/seed/crs09/200/200'),
(10, 'Chocolate Muffin', 3.50, 3, 'https://picsum.photos/seed/muf10/200/200'),
(11, 'Blueberry Scone', 3.50, 3, 'https://picsum.photos/seed/scn11/200/200'),
(12, 'Cinnamon Roll', 4.00, 3, 'https://picsum.photos/seed/cnr12/200/200'),
(13, 'Bagel', 2.50, 3, 'https://picsum.photos/seed/bgl13/200/200'),
(14, 'Danish Pastry', 3.75, 3, 'https://picsum.photos/seed/dns14/200/200'),
(15, 'Club Sandwich', 8.50, 4, 'https://picsum.photos/seed/clb15/200/200'),
(16, 'Chicken Wrap', 7.50, 4, 'https://picsum.photos/seed/wrp16/200/200'),
(17, 'BLT', 7.00, 4, 'https://picsum.photos/seed/blt17/200/200'),
(18, 'Turkey Panini', 8.00, 4, 'https://picsum.photos/seed/pnn18/200/200'),
(19, 'Veggie Sandwich', 6.50, 4, 'https://picsum.photos/seed/vgs19/200/200'),
(20, 'Caesar Salad', 8.00, 5, 'https://picsum.photos/seed/ces20/200/200'),
(21, 'Greek Salad', 7.50, 5, 'https://picsum.photos/seed/grk21/200/200'),
(22, 'Quinoa Bowl', 9.00, 5, 'https://picsum.photos/seed/qno22/200/200'),
(23, 'Poke Bowl', 11.00, 5, 'https://picsum.photos/seed/pok23/200/200'),
(24, 'Cheesecake', 6.00, 6, 'https://picsum.photos/seed/chs24/200/200'),
(25, 'Tiramisu', 6.50, 6, 'https://picsum.photos/seed/tir25/200/200'),
(26, 'Brownie', 4.50, 6, 'https://picsum.photos/seed/brw26/200/200'),
(27, 'Ice Cream', 3.50, 6, 'https://picsum.photos/seed/icm27/200/200'),
(28, 'French Fries', 4.00, 7, 'https://picsum.photos/seed/frm28/200/200'),
(29, 'Nachos', 5.50, 7, 'https://picsum.photos/seed/nch29/200/200'),
(30, 'Chicken Nuggets', 5.00, 7, 'https://picsum.photos/seed/ngt30/200/200'),
(31, 'Onion Rings', 4.50, 7, 'https://picsum.photos/seed/ong31/200/200'),
(32, 'Mixed Nuts', 3.00, 7, 'https://picsum.photos/seed/mxn32/200/200');

-- ຕາຕະລາງອໍເດີ POS
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cashier_id INT NULL,
    cashier_name VARCHAR(100) NOT NULL DEFAULT 'JD',
    status VARCHAR(20) NOT NULL DEFAULT 'completed', -- completed | held
    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5, 4) NOT NULL DEFAULT 0.0800,
    tax_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    discount_type VARCHAR(20) NULL DEFAULT 'percent', -- percent | fixed
    discount_value DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    total_items INT NOT NULL DEFAULT 0,
    payment_method VARCHAR(20) NULL, -- cash | card | digital
    cash_received DECIMAL(12, 2) NULL,
    change_amount DECIMAL(12, 2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ລາຍການສິນຄ້າໃນແຕ່ລະອໍເດີ
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(100) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(12, 2) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

CREATE INDEX idx_orders_status_created_at ON orders(status, created_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
