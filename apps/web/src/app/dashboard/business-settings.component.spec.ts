import { unconfiguredDays } from './business-settings.component';

describe('business hours helpers', () => { it('finds the days missing from the API response', () => expect(unconfiguredDays([{ day_of_week: 1 }, { day_of_week: 6 }])).toEqual([0, 2, 3, 4, 5])); });
