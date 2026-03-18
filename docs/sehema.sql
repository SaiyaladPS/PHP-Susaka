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

