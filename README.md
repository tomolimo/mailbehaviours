# mailbehaviours
Mail Behaviours GLPI Plugin may be used to create Tickets via emails with another requester than the email sender: to be able to create Tickets (or Followups) "On Behalf Of" via emails.
It gives the possivbility to have a different ticket requester than the ticket writer

Before GLPI 9.5, this feature was available in the MailAnalyzer plugin

It enables the so called `##From` feature, along with the `##CC` (add watcher for users or for groups)

### Use case
Many often Service desk people receive emails from requester directly in their own mailbox.
In this case they are obliged to manually create a new ticket in GLPI, and to copy/paste the content of the initial email (subject and body).
With this plugin, they only need to forward these emails to GLPI, after having added a ##From in front of the requester name (or email).


### GLPI compliance
It is currently compatible with GLPI 9.5 and 10.0

### Installation
Must be copied into __*glpifolder*__/plugins/mailbehaviours

To be installed and enabled via the plugins configuration page in GLPI.

### How to use
See wiki: (https://github.com/tomolimo/mailbehaviours/wiki)

### Issues
Please report any question/problem in the issue section: (https://github.com/tomolimo/mailbehaviours/issues)