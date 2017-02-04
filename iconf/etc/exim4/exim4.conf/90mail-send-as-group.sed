/^\s*acl_check_rcpt:/a \
    # Deny sending of mails to other server by groups without mail_ext privilege\
    deny\
      authenticated = \*\
      !domains = +local_domains\
      condition = ${lookup pgsql{ \\\
        SELECT 1 FROM groups g WHERE g.act = '${quote_pgsql:$authenticated_id}' \\\
        AND NOT EXISTS (SELECT 1 FROM privileges_assign p WHERE p.act = \\\
          g.act AND p.privilege = 'mail_ext' LIMIT 1)}}\
      message = You are not allowed to send e-mail to other servers.\\\
        \\n\\nSie dürfen keine E-Mails an andere Server schicken.\
