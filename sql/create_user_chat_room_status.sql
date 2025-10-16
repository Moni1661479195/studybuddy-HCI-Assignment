CREATE TABLE user_chat_room_status (
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    last_read_message_id INT DEFAULT 0,
    PRIMARY KEY (user_id, room_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE
);
