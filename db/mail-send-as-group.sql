/* 
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
/**
 * Author:  Felix Jacobi
 * Created: 24.01.2017
 */
CREATE TABLE mail_send_as_group_log (
    id                      SERIAL          PRIMARY KEY,
    msg_title               TEXT            NOT NULL,
    sender                  TEXT            NOT NULL 
                                            REFERENCES groups(act)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    msg_body                TEXT            NOT NULL,
    time                    TIMESTAMPTZ(0)  NOT NULL
);

CREATE TABLE mail_send_as_group_log_recipient (
    id                      SERIAL          PRIMARY KEY,
    msg_id                  INTEGER         NOT NULL 
                                            REFERENCES mail_send_as_group_log(id)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    recipient               TEXT            NOT NULL,
    recipient_display       TEXT            NOT NULL
);

CREATE TABLE mail_send_as_group_log_files (
    id                      SERIAL          PRIMARY KEY,
    msg_id                  INTEGER         NOT NULL 
                                            REFERENCES mail_send_as_group_log(id)
                                            ON UPDATE CASCADE
                                            ON DELETE CASCADE,
    mime                    TEXT            NOT NULL,
    name                    TEXT            NOT NULL--,
-- Do not store the files in the database - negative effects on the PHP memory usage
--    data                    TEXT            NOT NULL
);


GRANT SELECT, USAGE ON "mail_send_as_group_log_id_seq", "mail_send_as_group_log_recipient_id_seq", "mail_send_as_group_log_files_id_seq" TO "symfony";
GRANT SELECT ON "mail_send_as_group_log", "mail_send_as_group_log_recipient", "mail_send_as_group_log_files" TO "symfony";
GRANT SELECT ON "privileges_assign" TO "exim";
