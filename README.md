# edd-eu-vat-fix

This is small plugin that helps to correct issues with incorrect VAT rates when using EDD + EDD EU VAT plugin with the EDD Stripe Plugin.

The issue:

 - It looks like the issue only occurs for users that have an indvidual subscription plan to the Stripe Payment Gateway.
 - EDD no longer offer individual subscription plans
 - At some point, The EDD Stripe gateway stops updating for some users
 - This caused issues with the EDD EU VAT plugin which had a kind of caching affect
 - Users that live outside of the EU would sometimes get charged VAT
 - Users that should have been charged VAT would not be charged VAT

How this plugin addresses this:

 - Adds a filter on the orders screen to find EU purchases without VAT (excluding reverse charged orders as they still work ok)
 - Adds a filter on the orders screen to find no-EU purchases that have VAT (and of course, shouldn't)
 - Adds a metabox on the order screen to allow you to correct the VAT rate as follows:
    - Orders that shouldn't have VAT but do, have a button to automatically update the order and remove all the VAT info.
	   - It is recommended to issue a partial refund via the payment processor directly for the VAT amount.
	- Orders that should have VAT but dont - there is a button to add VAT.  
	   - This will be calculated based on the VAT rate for the country of the order.
	   - The total will remain the same
	   
**Note:** - this does not account for discounts and charges that may have been applied to the order.

**Note 2:** - this plugin has not been extensively tested - use at your own risk - take a backup and delete once you have finished using.
