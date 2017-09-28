# V 1.4.3

## Features
- restrict primary and secondary skills to 3 each

## Tech Debt
- add tests for unapproved Sessions command
- add tests for generate google credentials command
- add tests for unmatched Requests for Success
- update Session Ratings User to be the recipient of the rating
- add tests for cache slack User's command

# V 1.4.2

## Features
- paginate User's "My Interests" response
- implement User preferences for unapproved Sessions reminder

## Tech Debt
- add tests for weekly email Report command
- remove command to update User's location

# V 1.4.1

## Features
- implement reject Sessions endpoint

## Fixed Bugs
- fix Request link in Slack notifications
- fix Request Report' total number misalignment

## Tech Debt
- add tests for Sessions endpoints
- add tests for Requests endpoints
- implement validations for Google Calendar Helper
- add tests for Users endpoints
- add tests for Google Calendar Helper functions

# V 1.4.0

## Features
- implement personalized notifications settings
- show total logged hours on user's profile page
- update all User's Slack ID's with cached data
- remove sensitive data from weekly Fellows email

# V 1.3.0

## Features
- weekly notifications for unmatched Requests to fellows
- Google Calendar integration for scheduled Sessions
- remove Slack notifications from Lagos and Nairobi Fellows Channels

## Fixed Bugs
- fix User details response from AIS
- fix error for Requests without secondary Skills
- send emails when Mentor does not exist on Freckle when logging Sessions
- filter Mentor Requests for Offer Mentorship page

# V 1.2.0

## Features
- integrate Session logging with Freckle
- disable Ratings for rated Sessions
- paginate Dashboard Requests
- display Mentor name with matched Requests for Admins
- implement all Slack Users cache
- round up Average Time to Match Report
- implement global Exception handling
- move Models to appropriate namespace

# V 1.1.2

## Features
- implement Session Ratings
- implement Redis cache for all Slack users

## Fixed Bugs
- prevent Users from adding others Slack IDs

# V 1.1.1

## Features
- implement placed Mentee email notifications
- implement Slack notifications for new Requests

## Pending Bugs
- Users can register other people's slack handles

# V 1.1.0

## Features
- implement Sessions feature
- show if a Skill is linked to a Request or User

## Pending Bugs
- Slack notifications not working for mentor and mentee

# V 1.0.5

## Fixed Bugs
- fix Invalid Request error when trying to create Request

## Pending Bugs
- Slack notifications not working for mentor and mentee

# V 1.0.4

## Features


- implement Skills administration
- implement AISClient
- clean up environment variables

## Known Bugs
- Slack notifications not working for mentor and mentee

# V 1.0.3

## Features


- include CodeClimate configuration to use custom rule sets for PHPCS and PHPMD
- implement POST endpoints to allow users add skills

## Known Bugs
- Slack notifications not working for mentor and mentee

# V 1.0.2

## Features

- implement retrieval of user's Slack ID
- send Slack message to mentor when they have been selected
- send Slack message to mentee when mentor indicates interest

#### Skills

- `POST` endpoint to add skills

# V 1.0.1

## Features


#### Mentorship Requests
- append location to saved mentorship request data
- append mentee email to returned mentorship requests

#### Reports
- add average time to match to returned report

# V 1.0.0

## Features


#### Mentorship Requests
- `POST` endpoint to create mentorship requests
- `GET` endpoint to retrieve mentorship requests for self, mentorable and all
- `GET` endpoint to retrieve a single mentorship request
- `PATCH` endpoint to update mentor for a mentorship request
- `PATCH` endpoint to update interested mentors for a mentorship request

#### Skills
- `GET` endpoint to retrieve all skills

#### Status
- `GET` endpoint to retrieve all status

#### Users
- `GET` endpoint to retrieve users full profile

#### Reports
- `GET` endpoint to retrieve mentorship requests' stats across locations and within different periods:
    - skill count
    - total requests
    - total matched requests

#### Slack
- `GET` endpoint to retrieve and store a user's Slack ID
