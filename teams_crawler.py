import urllib2
import csv
import argparse
import os

from bs4 import BeautifulSoup

class ForgeTeamCrawler(object):
    """
    A command line utility program where: given the URL (local path or web
    address) containing sport teams, the team-filtering CSS selector and constant
    column values for competitions, teams, seasons, venue, the program extracts
    the teams from the HTML. Next, the constant columns are appended to each team
    found. The processed team data are then written to CSV compatible for the
    team upload tool on the Forge Sport website backend.
    """
    
    def __init__(self):
        self.AGENT_NAME = "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0"
        self.headers = {"User-Agent": self.AGENT_NAME}
        
    def run(self):
        parser = argparse.ArgumentParser(prog="Forge Sport Team Crawler")
        url_group = parser.add_mutually_exclusive_group(required=True)
        url_group.add_argument("-url", "-u", help="The URL to the page containing the teams to collate")
        url_group.add_argument("-path", "-p",
                            help="The local file path to the HTML file containing the teams to collate")
        parser.add_argument("selector", help="The CSS selector that picks out the team text in the HTML")
        parser.add_argument("-const_cols", "-c", help="The constant columns to append to the team names in order"+
                            " to conform to the CSV format of 'Name,Competitions,Teams,Seasons,Venue'"+
                            " required by the team upload tool on the Forge Sport website",
                            nargs=4, required=True)
        parser.add_argument("-target", "-t", help="The file path of the file to write the CSV data to",
                            required=True)
        args = parser.parse_args()
        
        if args.path:
            teams = self.get_clubs_local(args.path, args.selector, args.const_cols, parser)
        else:
            teams = self.get_clubs(args.url, args.selctor, args.const_cols, parser)
        
        if len(teams) > 0:
            self.csv_dump(args.target, teams)
        else:
            parser.error("No teams found. Please make sure you typed in the appropriate CSS selector")
    
    
    def get_clubs(self, url, team_selector, const_cols, parser):
        try:
            request = urllib2.Request(url, None, self.headers)
            teams_feed = urllib2.urlopen(request)
        except urllib2.URLError as e:
            parser.error(e.reason)
    
        teams = self.format_teams(teams_feed, team_selector, const_cols, parser)
    
        return teams
    
    def get_clubs_local(self, path, team_selector, const_cols, parser):
        if os.path.exists(path):
            with open(path, "rb") as f:
                teams = self.format_teams(f, team_selector, const_cols, parser)
        else:
            parser.error("Invalid path entered. Please make sure you've typed in the file path correctly.")
        
        return teams
    
    def format_teams(self, file_feed, team_selector, const_cols, parser):
        teams_soup = BeautifulSoup(file_feed.read(), "html.parser")
        try:
            teams_anchor = teams_soup.select(team_selector)
            teams = [[t.get_text(strip=True)]+const_cols for t in teams_anchor]
        except NotImplementedError as e:
            parser.error(e)

        return teams

    def csv_dump(self, target, teams):
        with open(target, "wb") as team_file:
            writer = csv.writer(team_file)
            writer.writerow(["Name","Competitions","Teams","Seasons","Venue"])
            writer.writerows(teams)

if __name__ == "__main__":
    ForgeTeamCrawler().run()

