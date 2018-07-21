# The Name Game

#### Working Demo
 - http://namegame.stevenandnicole.com

#### Objective
 - https://www.willowtreeapps.com/api/v1.0/profiles

- [x] Create an API that consumes the profile data from the URL above
- [x] make it possible to implement a full-featured game on top of it.
- [x] The client should be very simple
- [x] all of the logic for the game should be implemented in the server.
- [x] present the client/user with six faces and ask them to identify the listed name. 
- [x] Only spend about 8 hours on the project

***

##### If there is time, on top of that build in some other features:
- [x] Statistics: How many correct/incorrect attempts has the user made?
- [ ] How long does it take on average for a person to identify the subject?
- [x] Leaderboard: Show identifiers and scores for the top 10 scoring sessions.
- [x] Mat(t) mode: add a challenge mode where the server only presents the clients with Mat(t)s.
- [x] Reverse mode: Show one face and six names.

***

##### Insert your own idea here!
- [x] Add a Mike mode as there are plenty of them as well
- [x] Add medium and Hard modes which limit the number of guesses
- [x] Bonus timer for getting the answer correct faster to boost your score

***

##### Describe how you might handle authentication/authorization
 - authentication could be passed into the game class to register the user or read from the server variables as in the case of siteminder authentication.  I decided to go with the arcade style format where the user can enter in any type of name they desire when achieving a high score.  This name is then saved and defaulted in for the next time they make a high score.

***

### Notes on the project
As requested I decided to keep the code simple and lightweight using pure php for the backend and jQuery with Bootstrap for the front end.  A future enhancement would be to extend this project with a persistant connection using node.js.  I'd also like to add some animations to the choices but wanted to keep the project hours down so I had to cut some of the fun enhancements.
