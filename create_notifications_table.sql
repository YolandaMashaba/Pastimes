-- Create notifications table
CREATE TABLE IF NOT EXISTS tblnotifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('order_update', 'message', 'promotion', 'system') NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_link VARCHAR(500) NULL,
    action_text VARCHAR(100) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tbluser(user_id) ON DELETE CASCADE
);

-- Create index for faster queries
CREATE INDEX idx_user_id ON tblnotifications(user_id);
CREATE INDEX idx_is_read ON tblnotifications(is_read);
CREATE INDEX idx_created_at ON tblnotifications(created_at);
