- [x] Log incorrect password attempts, implement password fatigue
when we will not check if password is valid or not, but refuse immediately
if user entered wrong password 3 times in a row. Password fatigue lasts for 3 minutes.

- [ ] Add 2FA with sending code to email and waiting for this code from the user

- [ ] Add OAuth

- [x] Password, email, login constraints need to be added

- [ ] Add .env flag to autoapprove new accounts

- [ ] Captcha on new user registration

- [ ] Verify by email -> send link -> receive request -> activate account (valid for 5 minutes)